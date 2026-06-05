#version 150

#moj_import <fog.glsl>
#moj_import <grayscale.glsl>

uniform sampler2D Sampler0;

uniform vec4 ColorModulator;
uniform float FogStart;
uniform float FogEnd;
uniform vec4 FogColor;

in float vertexDistance;
in vec4 vertexColor;
in vec2 texCoord0;
in vec4 normal;

out vec4 fragColor;

in float grayscaleFactor;

void main() {
    vec4 color = texture(Sampler0, texCoord0) * vertexColor * ColorModulator;
    if (color.a < 0.1) {
        discard;
    }
    color = grayscale(color, grayscaleFactor);
    fragColor = linear_fog(color, vertexDistance, FogStart, FogEnd, FogColor);
}
