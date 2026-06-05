#version 150

layout(std140) uniform Fog {
    vec4 FogColor;
    float FogEnvironmentalStart;
    float FogEnvironmentalEnd;
    float FogRenderDistanceStart;
    float FogRenderDistanceEnd;
    float FogSkyEnd;
    float FogCloudsEnd;
};

const float ENVIRONMENTAL_DENSITY = 1.25; 
const float RENDER_DENSITY        = 1.35; 
const float SKY_DENSITY           = 1.00; 
const float CLOUD_DENSITY         = 1.00; 

const float ENVIRONMENTAL_START_MULT = 0.20;
const float ENVIRONMENTAL_END_MULT   = 0.96;
const float RENDER_START_MULT        = 0.20;
const float RENDER_END_MULT          = 1.00;

const vec3 FOG_TINT = vec3(0.8,1,1.2); 
const float FOG_TINT_INTENSITY = 0.8;        

float linear_fog_value(float vertexDistance, float fogStart, float fogEnd) {
    if (vertexDistance <= fogStart) return 0.0;
    if (vertexDistance >= fogEnd) return 1.0;
    return (vertexDistance - fogStart) / (fogEnd - fogStart);
}

float total_fog_value(float sphericalVertexDistance, float cylindricalVertexDistance,
                      float environmentalStart, float environmentalEnd,
                      float renderDistanceStart, float renderDistanceEnd) {

    float envStart = environmentalStart * ENVIRONMENTAL_START_MULT;
    float envEnd   = environmentalEnd   * ENVIRONMENTAL_END_MULT;
    float rendStart = renderDistanceStart * RENDER_START_MULT;
    float rendEnd   = renderDistanceEnd   * RENDER_END_MULT;

    float environmentalFog = linear_fog_value(
        sphericalVertexDistance,
        envStart * (1.8 / ENVIRONMENTAL_DENSITY),
        envEnd   / ENVIRONMENTAL_DENSITY
    );

    float renderFog = linear_fog_value(
        cylindricalVertexDistance,
        rendStart * (1.0 / RENDER_DENSITY),
        rendEnd   / RENDER_DENSITY
    );

    return max(environmentalFog, renderFog);
}

vec4 apply_fog(vec4 inColor, float sphericalVertexDistance, float cylindricalVertexDistance,
               float environmentalStart, float environmentalEnd,
               float renderDistanceStart, float renderDistanceEnd,
               vec4 fogColor) {

    float fogValue = total_fog_value(
        sphericalVertexDistance, cylindricalVertexDistance,
        environmentalStart, environmentalEnd,
        renderDistanceStart, renderDistanceEnd
    );

    vec3 tintedFog = mix(fogColor.rgb, fogColor.rgb * FOG_TINT, FOG_TINT_INTENSITY);

    float skyHeightFactor = clamp((sphericalVertexDistance - (FogSkyEnd * 0.5)) / (FogSkyEnd * 0.5), 0.0, 1.0);
    vec3 finalFog = mix(tintedFog, fogColor.rgb, skyHeightFactor);

    vec3 mixedColor = mix(inColor.rgb, finalFog, fogValue * fogColor.a);
    return vec4(mixedColor, inColor.a);
}

float fog_spherical_distance(vec3 pos) {
    return length(pos);
}

float fog_cylindrical_distance(vec3 pos) {
    float distXZ = length(pos.xz);
    float distY = abs(pos.y);
    return max(distXZ, distY);
}

//by DR7 https://modrinth.com/user/DR7