#version 330

#moj_import <minecraft:fog.glsl>
#moj_import <minecraft:dynamictransforms.glsl>

uniform sampler2D Sampler0;

in float sphericalVertexDistance;
in float cylindricalVertexDistance;

#ifdef PER_FACE_LIGHTING
in vec4 vertexPerFaceColorBack;
in vec4 vertexPerFaceColorFront;
#else
in vec4 vertexColor;
#endif

in vec4 lightMapColor;
in vec4 overlayColor;
in vec2 texCoord0;

out vec4 fragColor;

vec3 applyVibrance(vec3 color, float vibranceStrength) {
    float luminance = dot(color, vec3(0.3));
    float maxChannel = max(max(color.r, color.g), color.b);
    float minChannel = min(min(color.r, color.g), color.b);
    float saturation = maxChannel - minChannel;
    float vibranceFactor = (1.0 - saturation) * vibranceStrength;
    vec3 gray = vec3(luminance);
    return mix(gray, color, 1.0 + vibranceFactor);
}

vec3 reduceOverbrightWhites(vec3 color) {
    float luminance = dot(color, vec3(0.333));
    float maxC = max(max(color.r, color.g), color.b);
    float minC = min(min(color.r, color.g), color.b);
    float saturation = maxC - minC;
    float whiteFactor = smoothstep(0.65, 1.0, luminance) * (1.0 - smoothstep(0.0, 1.0, saturation));
    color *= mix(1.0, 0.9, whiteFactor);
    return color;
}

vec3 adjustPixelLuminanceGradient(vec3 color) {
    float brightness = dot(color, vec3(0.333));
    float distance = abs(brightness - 0.325);
    float falloff = 1.0 - smoothstep(0.0, 0.65, distance);
    float boost = mix(1.0, 1.0, falloff);
    return clamp(color * boost, 0.0, 1.0);
}

void main() {
    vec4 baseColor = texture(Sampler0, texCoord0);

    vec4 color = baseColor;

#ifdef ALPHA_CUTOUT
    if (color.a < ALPHA_CUTOUT) {
        discard;
    }
#endif

#ifdef PER_FACE_LIGHTING
    vec4 vertexLighting = gl_FrontFacing ? vertexPerFaceColorFront : vertexPerFaceColorBack;
    color *= vertexLighting * ColorModulator;
#else
    color *= vertexColor * ColorModulator;
#endif

#ifndef NO_OVERLAY
    color.rgb = mix(overlayColor.rgb, color.rgb, overlayColor.a);
#endif

#ifndef EMISSIVE
    color *= lightMapColor;
#endif

    color.rgb *= 1.1;

    color.rgb = applyVibrance(color.rgb, 0.3);

#ifdef PER_FACE_LIGHTING
    float vertexBrightness = max(max(vertexLighting.r, vertexLighting.g), vertexLighting.b);
#else
    float vertexBrightness = max(max(vertexColor.r, vertexColor.g), vertexColor.b);
#endif

    if (vertexBrightness <= 0.65) { color.rgb *= vec3(0.9478, 0.9743, 1.0085); }
    if (vertexBrightness <= 0.645) { color.rgb *= vec3(0.9478, 0.9743, 1.0085); }
    if (vertexBrightness <= 0.64) { color.rgb *= vec3(0.9478, 0.9743, 1.0085); }
    if (vertexBrightness <= 0.635) { color.rgb *= vec3(0.9478, 0.9743, 1.0085); }
    if (vertexBrightness <= 0.63) { color.rgb *= vec3(0.9478, 0.9743, 1.0085); }
    if (vertexBrightness <= 0.625) { color.rgb *= vec3(0.9478, 0.9743, 1.0085); }
    if (vertexBrightness <= 0.62) { color.rgb *= vec3(0.9478, 0.9743, 1.0085); }
    if (vertexBrightness <= 0.615) { color.rgb *= vec3(0.9478, 0.9743, 1.0085); }
    if (vertexBrightness <= 0.61) { color.rgb *= vec3(0.9478, 0.9743, 1.0085); }
    if (vertexBrightness <= 0.605) { color.rgb *= vec3(0.9478, 0.9743, 1.0085); }

    if (vertexBrightness <= 0.186)  { color.rgb *= vec3(0.96933); }
    if (vertexBrightness <= 0.1885) { color.rgb *= vec3(0.96933); }
    if (vertexBrightness <= 0.191)  { color.rgb *= vec3(0.96933); }
    if (vertexBrightness <= 0.1935) { color.rgb *= vec3(0.96933); }
    if (vertexBrightness <= 0.196)  { color.rgb *= vec3(0.96933); }
    if (vertexBrightness <= 0.1985) { color.rgb *= vec3(0.96933); }
    if (vertexBrightness <= 0.201)  { color.rgb *= vec3(0.96933); }
    if (vertexBrightness <= 0.2035) { color.rgb *= vec3(0.96933); }
    if (vertexBrightness <= 0.206)  { color.rgb *= vec3(0.96933); }
    if (vertexBrightness <= 0.2085) { color.rgb *= vec3(0.96933); }

    if (vertexBrightness <= 0.3)  { color.rgb *= vec3(1.056); }
    if (vertexBrightness <= 0.27) { color.rgb *= vec3(1.056); }
    if (vertexBrightness <= 0.24) { color.rgb *= vec3(1.056); }
    if (vertexBrightness <= 0.21) { color.rgb *= vec3(1.056); }
    if (vertexBrightness <= 0.18) { color.rgb *= vec3(1.056); }
    if (vertexBrightness <= 0.15) { color.rgb *= vec3(1.056); }
    if (vertexBrightness <= 0.12) { color.rgb *= vec3(1.056); }
    if (vertexBrightness <= 0.09) { color.rgb *= vec3(1.056); }
    if (vertexBrightness <= 0.06) { color.rgb *= vec3(1.056); }
    if (vertexBrightness <= 0.001) { color.rgb *= vec3(1.056); }

    color.rgb = adjustPixelLuminanceGradient(color.rgb);

    color.rgb = reduceOverbrightWhites(color.rgb);

    vec3 brightened = color.rgb * 1.0;
    float intensity = dot(brightened, vec3(0.299, 0.587, 0.114));
    vec3 saturated = mix(vec3(intensity), brightened, 1.0);

    vec4 finalColor = vec4(saturated, color.a);

    fragColor = apply_fog(
        finalColor,
        sphericalVertexDistance, cylindricalVertexDistance,
        FogEnvironmentalStart, FogEnvironmentalEnd,
        FogRenderDistanceStart, FogRenderDistanceEnd,
        FogColor
    );
}

//by DR7 https://modrinth.com/user/DR7