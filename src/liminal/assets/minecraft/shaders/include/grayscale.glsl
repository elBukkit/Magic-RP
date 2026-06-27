#version 330

float radius = 0.0; // The bigger the number, the lesser the radius. Negative values are also accepted.
float smoothness = 256.0; // The bigger the number, the smoother the transition. Zero will completely disable the effect.

float getGrayscaleFactor(ivec2 UV2) {
    return getFactor(UV2, radius, smoothness);
}

float getFactor(ivec2 uv, float radius, float smoothness) {
    float grayscaleFactor = 0.0;
    if (uv.x < 256) {
        float distance = abs(1.0 - uv.x);
        grayscaleFactor = 1.0 - smoothstep(radius, smoothness, distance);
    } else {
        grayscaleFactor = 0.0;
    }
    return grayscaleFactor;
}

vec4 grayscale(vec4 color, float grayscaleFactor) {
    float gray = (color.r + color.g + color.b) / 3.0;
    color = mix(color, vec4(gray, gray, gray, color.a), grayscaleFactor);
    return color;
}