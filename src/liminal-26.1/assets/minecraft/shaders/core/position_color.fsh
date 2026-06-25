#version 150

#moj_import <minecraft:dynamictransforms.glsl>

in vec4 vertexColor;

out vec4 fragColor;

void main() {
    vec4 color = vertexColor;
    if (color.a == 0.0) {
        discard;
    }
    vec3 skyTint = vec3(3.57);
    color.rgb *= skyTint;
    fragColor = color * ColorModulator;
}

//by DR7 https://modrinth.com/user/DR7