#version 150

#moj_import <minecraft:fog.glsl>
#moj_import <minecraft:dynamictransforms.glsl>

uniform sampler2D Sampler0;

in float sphericalVertexDistance;
in float cylindricalVertexDistance;
in vec2 texCoord0;
in vec4 vertexColor;

out vec4 fragColor;

void main() {
    vec4 color = texture(Sampler0, texCoord0) * vertexColor * ColorModulator;
    if (color.a < 0.1) {
        discard;
    }
    float brightness = (color.r + color.g + color.b) / 3.0;
    if (brightness > 0.306) {
        color.rgb *= 1.3;
    }
    if (brightness < 0.306) {
        color.rgb *= (0.7177, 0.7464, 1.0819);
    }
    if (brightness < 0.152) {
        color.rgb *= (0.76, 0.76, 0.86);
    }
    fragColor = apply_fog(color, sphericalVertexDistance, cylindricalVertexDistance, FogEnvironmentalStart, FogEnvironmentalEnd, FogRenderDistanceStart, FogRenderDistanceEnd, FogColor);
}

//by DR7 https://modrinth.com/user/DR7