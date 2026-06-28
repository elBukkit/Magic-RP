#version 330

const float height = 0.25;   // 0.1 to 0.5 works best
const float frequency = 0.5; // I think the default is the best, but you can play around with it more
const float speed = 3000.0;  // 500 - 5000 works best

float time = GameTime * speed;

float getWaveOffset(sampler2D Sampler0, vec2 UV0, vec3 Position) {

    if (round(texture(Sampler0, UV0).a * 1000.0) != 706.0) return 0.0;
    float x = abs(Position.x - 8.0);
    float z = abs(Position.z - 8.0);

    float xWave = 1.1 * height * sin((round(x * frequency) + time * 0.9));
    float zWave = 0.9 * height * cos((round(z * frequency) + time * 1.1));

    return (xWave + zWave) * 0.25;
}
