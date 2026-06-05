#version 330

// ======================================================================================================== //
// ⚙️ Re-Shaded Color Settings (1.0 is the default value for each configuration) ⚙️
// ======================================================================================================== //

const float BRIGHTNESS = 1.0;
const float CONTRAST_STRENGTH = 1.0;
const float SATURATION_STRENGTH = 1.0;
const float SUN_BRIGHTNESS = 1.0;

// ======================================================================================================== //
// ✅ To save your settings, save this file and press F3+T in your world (or Fn+F3+T on some laptops) ✅   //
// ======================================================================================================== //

#moj_import <minecraft:fog.glsl>
#moj_import <minecraft:globals.glsl>
#moj_import <minecraft:chunksection.glsl>
#moj_import <grayscale.glsl>

uniform sampler2D Sampler0;
uniform vec4 ColorModulator;
uniform float FogStart;
uniform float FogEnd;

in float sphericalVertexDistance;
in float cylindricalVertexDistance;
in vec4 vertexColor;
in vec2 texCoord0;
in float vertexDistance;
in float grayscaleFactor;

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

    float lumFactor = smoothstep(0.6, 0.95, luminance);
    
    float satFactor = 1.0 - smoothstep(0.0, 0.4, saturation); 
    
    float whiteFactor = lumFactor * satFactor;
    
    color *= mix(1.0, 0.95, whiteFactor); 
    return color;
}

vec3 adjustPixelLuminanceGradient(vec3 color) {
    float brightness = dot(color, vec3(0.333));
    float distance = abs(brightness - 0.325);
    float falloff = 1.0 - smoothstep(0.0, 0.65, distance);
    float boost = mix(1.0, 2, falloff);
    return clamp(color * boost * BRIGHTNESS, 0.0, 1.0);
}

vec3 increaseContrastByLuminance(vec3 color) {
    float luminance = dot(color, vec3(0.333));

    float overFactor  = smoothstep(0.5, 1.0, luminance);
    float underFactor = 1.0 - smoothstep(0.0, 0.5, luminance);

    vec3 brighter = mix(color, vec3(1.0), overFactor * 0.0);
    vec3 darker   = mix(color, vec3(0.0), underFactor * 0.1 * CONTRAST_STRENGTH);

    return mix(darker, brighter, overFactor);
}

vec4 sampleNearest(sampler2D sampler, vec2 uv, vec2 pixelSize, vec2 du, vec2 dv, vec2 texelScreenSize) {
    // Convert our UV back up to texel coordinates and find out how far over we are from the center of each pixel
    vec2 uvTexelCoords = uv / pixelSize;
    vec2 texelCenter = round(uvTexelCoords) - 0.5f;
    vec2 texelOffset = uvTexelCoords - texelCenter;

    // Move our offset closer to the texel center based on texel size on screen
    texelOffset = (texelOffset - 0.5f) * pixelSize / texelScreenSize + 0.5f;
    texelOffset = clamp(texelOffset, 0.0f, 1.0f);

    uv = (texelCenter + texelOffset) * pixelSize;
    return textureGrad(sampler, uv, du, dv);
}

vec4 sampleNearest(sampler2D source, vec2 uv, vec2 pixelSize) {
    vec2 du = dFdx(uv);
    vec2 dv = dFdy(uv);
    vec2 texelScreenSize = sqrt(du * du + dv * dv);
    return sampleNearest(source, uv, pixelSize, du, dv, texelScreenSize);
}

// Rotated Grid Super-Sampling
vec4 sampleRGSS(sampler2D source, vec2 uv, vec2 pixelSize) {
    vec2 du = dFdx(uv);
    vec2 dv = dFdy(uv);

    vec2 texelScreenSize = sqrt(du * du + dv * dv);
    float maxTexelSize = max(texelScreenSize.x, texelScreenSize.y);

    float minPixelSize = min(pixelSize.x, pixelSize.y);

    float transitionStart = minPixelSize * 1.0;
    float transitionEnd = minPixelSize * 2.0;
    float blendFactor = smoothstep(transitionStart, transitionEnd, maxTexelSize);

    float duLength = length(du);
    float dvLength = length(dv);
    float minDerivative = min(duLength, dvLength);
    float maxDerivative = max(duLength, dvLength);

    float effectiveDerivative = sqrt(minDerivative * maxDerivative);

    float mipLevelExact = max(0.0, log2(effectiveDerivative / minPixelSize));

    float mipLevelLow = floor(mipLevelExact);
    float mipLevelHigh = mipLevelLow + 1.0;
    float mipBlend = fract(mipLevelExact);

    const vec2 offsets[4] = vec2[](
    vec2(0.125, 0.375),
    vec2(-0.125, -0.375),
    vec2(0.375, -0.125),
    vec2(-0.375, 0.125)
    );

    vec4 rgssColorLow = vec4(0.0);
    vec4 rgssColorHigh = vec4(0.0);
    for (int i = 0; i < 4; ++i) {
        vec2 sampleUV = uv + offsets[i] * pixelSize;
        rgssColorLow += textureLod(source, sampleUV, mipLevelLow);
        rgssColorHigh += textureLod(source, sampleUV, mipLevelHigh);
    }
    rgssColorLow *= 0.25;
    rgssColorHigh *= 0.25;

    vec4 rgssColor = mix(rgssColorLow, rgssColorHigh, mipBlend);

    vec4 nearestColor = sampleNearest(source, uv, pixelSize, du, dv, texelScreenSize);

    return mix(nearestColor, rgssColor, blendFactor);
}

void main() {
    vec4 color = (UseRgss == 1 ? sampleRGSS(Sampler0, texCoord0, 1.0f / TextureSize) : sampleNearest(Sampler0, texCoord0, 1.0f / TextureSize)) * vertexColor;
    color = mix(FogColor * vec4(1, 1, 1, color.a), color, ChunkVisibility);
#ifdef ALPHA_CUTOUT
    if (color.a < ALPHA_CUTOUT) {
        discard;
    }
#endif

    color.rgb *= 0.62 * SUN_BRIGHTNESS;

    float vertexBrightness = max(max(vertexColor.r, vertexColor.g), vertexColor.b);
    
    color.rgb = applyVibrance(color.rgb, 0.5 * SATURATION_STRENGTH);
    
    if (vertexBrightness <= 0.655) { color.rgb *= vec3(0.9478, 0.9743, 1.0085); }
    if (vertexBrightness <= 0.66) { color.rgb *= vec3(0.9478, 0.9743, 1.0085); }
    if (vertexBrightness <= 0.665) { color.rgb *= vec3(0.9478, 0.9743, 1.0085); }
    if (vertexBrightness <= 0.67) { color.rgb *= vec3(0.9478, 0.9743, 1.0085); }
    if (vertexBrightness <= 0.675) { color.rgb *= vec3(0.9478, 0.9743, 1.0085); }
    if (vertexBrightness <= 0.68) { color.rgb *= vec3(0.9478, 0.9743, 1.0085); }
    if (vertexBrightness <= 0.685) { color.rgb *= vec3(0.9478, 0.9743, 1.0085); }
    if (vertexBrightness <= 0.69) { color.rgb *= vec3(0.9478, 0.9743, 1.0085); }
    if (vertexBrightness <= 0.695) { color.rgb *= vec3(0.9478, 0.9743, 1.0085); }
    if (vertexBrightness <= 0.7) { color.rgb *= vec3(0.9478, 0.9743, 1.0085); }

    if (vertexBrightness <= 0.11)  { color.rgb *= vec3(0.96473); }
    if (vertexBrightness <= 0.1105)  { color.rgb *= vec3(0.96473); }
    if (vertexBrightness <= 0.111)  { color.rgb *= vec3(0.96473); }
    if (vertexBrightness <= 0.1115)  { color.rgb *= vec3(0.96473); }
    if (vertexBrightness <= 0.112)  { color.rgb *= vec3(0.96473); }
    if (vertexBrightness <= 0.1125)  { color.rgb *= vec3(0.96473); }
    if (vertexBrightness <= 0.113)  { color.rgb *= vec3(0.96473); }
    if (vertexBrightness <= 0.1135)  { color.rgb *= vec3(0.96473); }
    if (vertexBrightness <= 0.114)  { color.rgb *= vec3(0.96473); }
    if (vertexBrightness <= 0.1145)  { color.rgb *= vec3(0.96473); }

    color.rgb = adjustPixelLuminanceGradient(color.rgb);

    color.rgb = increaseContrastByLuminance(color.rgb);

    color.rgb = reduceOverbrightWhites(color.rgb);

    vec3 brightened = color.rgb * BRIGHTNESS;
    float intensity = dot(brightened, vec3(0.299, 0.587, 0.114));
    vec3 saturated = mix(vec3(intensity), brightened, 1.0);

    vec4 finalColor = vec4(saturated, color.a);

    finalColor = grayscale(finalColor, grayscaleFactor);
    fragColor = apply_fog(finalColor, sphericalVertexDistance, cylindricalVertexDistance, FogEnvironmentalStart, FogEnvironmentalEnd, FogRenderDistanceStart, FogRenderDistanceEnd, FogColor);
}

//by DR7 https://modrinth.com/user/DR7