#version 150

#moj_import <minecraft:fog.glsl>


in float vertexDistance;
in vec4 vertexColor;

out vec4 fragColor;

void main() {
    vec4 color = vertexColor;
    float brightness = (color.r + color.g + color.b) / 3.0;
    if (brightness < 0.8) {
        color.rgb *= vec3(0.85,0.9,1.3);
    }
    if (brightness < 1) {
        color.rgb *= vec3(1.3);
    }
    color.a *= 1.0f - linear_fog_value(vertexDistance, 0, FogCloudsEnd);
    fragColor = color;
}

//by DR7 https://modrinth.com/user/DR7