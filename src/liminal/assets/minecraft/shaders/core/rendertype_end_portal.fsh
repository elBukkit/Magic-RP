#version 330

#moj_import <minecraft:fog.glsl>
#moj_import <minecraft:globals.glsl>          // GameTime
#moj_import <minecraft:dynamictransforms.glsl> // ColorModulator (vanilla tint)

uniform sampler2D Sampler0;                    // equirectangular skybox (end_sky.png)

in vec3 worldDir;
in float sphericalVertexDistance;
in float cylindricalVertexDistance;

out vec4 fragColor;

// ----------------------------- TWEAKABLES ----------------------------
const vec3  TINT       = vec3(1.0, 1.0, 1.0); // multiplies the sky (1,1,1 = no tint)
const float BRIGHTNESS = 1.0;                 // overall gain (>1 brighter, <1 dimmer)
const float SPIN_SPEED = 0.0;                 // sky rotation, in REVOLUTIONS per
                                              // in-game day, about the vertical axis.
                                              // 0 = static. Try 0.25 for a slow drift.
                                              // (Snaps once/day when GameTime wraps.)
const bool  APPLY_FOG  = true;                // blend into distance fog like vanilla
const bool  USE_VANILLA_TINT = true;          // honor /data tint applied to the block
// ---------------------------------------------------------------------

const float PI = 3.14159265358979323846;

// Map a normalized direction (+Y up) to equirectangular UVs.
vec2 equirect_uv(vec3 dir) {
    float u = 0.5 + atan(dir.z, dir.x) / (2.0 * PI);
    float v = 0.5 - asin(clamp(dir.y, -1.0, 1.0)) / PI;
    return vec2(u, v);
}

void main() {
    vec3 dir = normalize(worldDir);

    if (SPIN_SPEED != 0.0) {
        float a = GameTime * SPIN_SPEED * 2.0 * PI;
        float s = sin(a);
        float c = cos(a);
        dir = vec3(c * dir.x - s * dir.z, dir.y, s * dir.x + c * dir.z);
    }

    vec3 sky = texture(Sampler0, equirect_uv(dir)).rgb;

    vec3 color = sky * TINT * BRIGHTNESS;
    if (USE_VANILLA_TINT) {
        color *= ColorModulator.rgb;
    }

    vec4 outColor = vec4(color, 1.0);

    if (APPLY_FOG) {
        outColor = apply_fog(outColor, sphericalVertexDistance, cylindricalVertexDistance,
                             FogEnvironmentalStart, FogEnvironmentalEnd,
                             FogRenderDistanceStart, FogRenderDistanceEnd, FogColor);
    }

    fragColor = outColor;
}
