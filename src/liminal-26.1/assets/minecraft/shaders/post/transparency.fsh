#version 400
#moj_import <minecraft:globals.glsl>

uniform sampler2D MainSampler;
uniform sampler2D MainDepthSampler;
uniform sampler2D TranslucentSampler;
uniform sampler2D TranslucentDepthSampler;
uniform sampler2D ItemEntitySampler;
uniform sampler2D ItemEntityDepthSampler;
uniform sampler2D ParticlesSampler;
uniform sampler2D ParticlesDepthSampler;
uniform sampler2D WeatherSampler;
uniform sampler2D WeatherDepthSampler;
uniform sampler2D CloudsSampler;
uniform sampler2D CloudsDepthSampler;

in vec2 texCoord;
out vec4 fragColor;

#define SSAO_SAMPLES 32         
#define SSAO_RADIUS 8.0           
#define SSAO_STRENGTH 1.0        
#define SSAO_MIN_TOL 0.02         
#define SSAO_MAX_TOL 0.3          
#define SSAO_COLOR_TINT 0.95

#define REFL_SAMPLES 1           
#define REFL_RADIUS 0          
#define BORDER_STRENGTH 0      
#define FACE_STRENGTH 0        
#define REFL_MIN_TOL 0.0         
#define REFL_MAX_TOL 2.0  

#define FADE_START_DISTANCE 0.0 
#define FADE_END_DISTANCE 15.0   

#define NUM_LAYERS 6
vec4 color_layers[NUM_LAYERS];
float depth_layers[NUM_LAYERS];
int active_layers = 0;

ivec2 screenSize = textureSize(MainSampler, 0);

void try_insert(vec4 color, float depth) {
    if (color.a == 0.0) return;

    color_layers[active_layers] = color;
    depth_layers[active_layers] = depth;

    int jj = active_layers++;
    int ii = jj - 1;
    while (jj > 0 && depth_layers[jj] > depth_layers[ii]) {
        float depthTemp = depth_layers[ii];
        depth_layers[ii] = depth_layers[jj];
        depth_layers[jj] = depthTemp;

        vec4 colorTemp = color_layers[ii];
        color_layers[ii] = color_layers[jj];
        color_layers[jj] = colorTemp;

        jj = ii--;
    }
}

vec3 blend(vec3 dst, vec4 src) {
    return (dst * (1.0 - src.a)) + src.rgb;
}

float near = 0.1;
float far = 1000.0;
float linearizeDepth(float depth) {
    float z = depth * 2.0 - 1.0;
    return (near * far) / (far + near - z * (far - near));
}

void main() {

    vec4 baseOpaque = texture(MainSampler, texCoord);
    float rawDepth = texture(MainDepthSampler, texCoord).r;
    
    if (rawDepth < 0.999) {
        float centerDepth = linearizeDepth(rawDepth);
        float distFade = 1.0 - smoothstep(FADE_START_DISTANCE, FADE_END_DISTANCE, centerDepth);

        if (distFade > 0.001) {
            float occlusion = 0.0;
            
            float lightBorderHighlight = 0.0;
            float faceHighlight = 0.0; 
            
            vec3 envReflection = vec3(0.0);
            float envWeight = 0.0;
            
            vec2 pixelSize = 1.0 / vec2(screenSize);

            int ssaoIterations = SSAO_SAMPLES / 2;
            float ssaoAngleStep = 3.14159265 / float(ssaoIterations); 
            
            float idealSsaoRadius = SSAO_RADIUS * (5.0 / max(centerDepth, 0.1));
            float actualSsaoRadius = clamp(idealSsaoRadius, 1.0, 30.0);
            float ssaoRadiusRatio = actualSsaoRadius / idealSsaoRadius;
            
            float currentSsaoMinTol = SSAO_MIN_TOL * ssaoRadiusRatio;
            float currentSsaoMaxTol = SSAO_MAX_TOL * ssaoRadiusRatio;

            for(int i = 0; i < ssaoIterations; i++) {
                float angle = float(i) * ssaoAngleStep; 
                vec2 offset = vec2(cos(angle), sin(angle)) * pixelSize * actualSsaoRadius;
                
                float depth1 = linearizeDepth(texture(MainDepthSampler, texCoord + offset).r);
                float depth2 = linearizeDepth(texture(MainDepthSampler, texCoord - offset).r);
                
                float diff = (2.0 * centerDepth) - (depth1 + depth2);

                if (diff > currentSsaoMinTol && diff < currentSsaoMaxTol) {
                    float weight = 1.0 - smoothstep(currentSsaoMinTol, currentSsaoMaxTol, diff);
                    occlusion += weight;
                } 
            }
            occlusion /= float(ssaoIterations);

            float reflAngleStep = 6.28318 / float(REFL_SAMPLES);
            
            float idealReflRadius = REFL_RADIUS * (5.0 / max(centerDepth, 0.1));
            float actualReflRadius = clamp(idealReflRadius, 1.0, 60.0);
            float reflRadiusRatio = actualReflRadius / idealReflRadius;
            
            float currentReflMinTol = REFL_MIN_TOL * reflRadiusRatio;
            float currentReflMaxTol = REFL_MAX_TOL * reflRadiusRatio;

            for(int i = 0; i < REFL_SAMPLES; i++) {
                float angle = float(i) * reflAngleStep;
                vec2 offset = vec2(cos(angle), sin(angle)) * pixelSize * actualReflRadius;
                
                float sampleDepth = linearizeDepth(texture(MainDepthSampler, texCoord + offset).r);
                float diff = centerDepth - sampleDepth;

                if (diff < -currentReflMinTol && diff > -currentReflMaxTol) {
                    float weight = 1.0 - smoothstep(currentReflMinTol, currentReflMaxTol, -diff);
                    
                    float borderW = pow(weight, 6.0); // Afilado
                    float faceW = pow(weight, 1.5);   // Suave
                    
                    lightBorderHighlight += borderW; 
                    faceHighlight += faceW;
                    
                    vec2 blurOffset = offset * 1.5;
                    float rawBlurDepth = texture(MainDepthSampler, texCoord + blurOffset).r;
                    if (rawBlurDepth < 0.9999) { 
                        envReflection += texture(MainSampler, texCoord + blurOffset).rgb * faceW;
                        envWeight += faceW;
                    }
                }
            }
            lightBorderHighlight /= float(REFL_SAMPLES);
            faceHighlight /= float(REFL_SAMPLES);
            
            if (envWeight > 0.0001) {
                envReflection /= envWeight;
            } else {
                envReflection = baseOpaque.rgb; 
            }
            

            occlusion *= distFade;
            
            float aoIntensity = clamp(occlusion * SSAO_STRENGTH, 0.0, 1.0);
            
            vec3 shadowMultiplier = mix(vec3(1.0), baseOpaque.rgb * SSAO_COLOR_TINT, aoIntensity);

            baseOpaque.rgb *= shadowMultiplier;

            float luma = dot(baseOpaque.rgb, vec3(0.299, 0.587, 0.114));
            float lightFactor = smoothstep(0.01, 0.18, luma);

            vec3 finalLightBorder = baseOpaque.rgb * (lightBorderHighlight * BORDER_STRENGTH);

            vec3 finalFaceReflection = envReflection * (faceHighlight * FACE_STRENGTH * lightFactor);
            
            vec3 totalReflections = (finalLightBorder + finalFaceReflection) * distFade;

            baseOpaque.rgb = baseOpaque.rgb + totalReflections - (baseOpaque.rgb * totalReflections);
        }
    }
    
    color_layers[0] = vec4(baseOpaque.rgb, 1.0);
    depth_layers[0] = rawDepth;
    active_layers = 1;

    try_insert(texture(TranslucentSampler, texCoord), texture(TranslucentDepthSampler, texCoord).r);
    try_insert(texture(ItemEntitySampler, texCoord), texture(ItemEntityDepthSampler, texCoord).r);
    try_insert(texture(ParticlesSampler, texCoord), texture(ParticlesDepthSampler, texCoord).r);
    try_insert(texture(WeatherSampler, texCoord), texture(WeatherDepthSampler, texCoord).r);
    
    try_insert(texture(CloudsSampler, texCoord), 0.9999);

    vec3 finalColor = color_layers[0].rgb;
    for (int ii = 1; ii < active_layers; ++ii) {
        finalColor = blend(finalColor, color_layers[ii]);
    }

    fragColor = vec4(finalColor, 1.0);
}

//by DR7 https://modrinth.com/user/DR7