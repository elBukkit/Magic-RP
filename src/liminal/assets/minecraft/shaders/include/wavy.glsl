#version 330

const float maxHeight = 1.25;
const float frequency = 0.75;
const float speed = 3000.0;

float time = GameTime * speed;

float getWaveOffset(sampler2D Sampler0, vec2 UV0, vec3 Position) {
    if (round(texture(Sampler0, UV0).a * 1000.0) != 706.0) return 0.0;
    float x = abs(Position.x - 8.0);
    float z = abs(Position.z - 8.0);

    // Smooth transition, highest at midnight
    // Luminance of fog color — low at night, higher during day
    float fogBrightness = dot(FogColor.rgb, vec3(0.299, 0.587, 0.114));

    // Invert so nightFactor is 1 at night, 0 at day
    float nightFactor = 1.0 - clamp(fogBrightness * 4.0, 0.0, 0.8);

    float height = maxHeight * nightFactor;

    float xWave = 1.1 * height * sin((round(x * frequency) + time * 0.9));
    float zWave = 0.9 * height * cos((round(z * frequency) + time * 1.1));

    float heightOffset = height / 2;
    return (xWave + zWave) * 0.25 - heightOffset;
}
