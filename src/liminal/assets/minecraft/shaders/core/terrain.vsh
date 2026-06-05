#version 330

#moj_import <minecraft:fog.glsl>
#moj_import <minecraft:light.glsl>
#moj_import <minecraft:globals.glsl>
#moj_import <minecraft:chunksection.glsl>
#moj_import <minecraft:projection.glsl>
#moj_import <grayscale.glsl>

in vec3 Position;
in vec4 Color;
in vec2 UV0;
in ivec2 UV2;
in vec3 Normal;

uniform sampler2D Sampler2;
uniform sampler2D Sampler0;

out float sphericalVertexDistance;
out float cylindricalVertexDistance;
out vec4 vertexColor;
out vec2 texCoord0;
out float grayscaleFactor;

vec4 minecraft_sample_lightmap(sampler2D lightMap, ivec2 uv) {
    return texture(lightMap, clamp((uv / 256.0) + 0.5 / 16.0, vec2(0.5 / 16.0), vec2(15.5 / 16.0)));
}

const float maxHeight = 1.25;
const float frequency = 0.75;
const float speed = 3000.0;
float time = GameTime * speed;

float getWaveOffset() {
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

void main() {
    vec3 pos = Position + (ChunkPosition - CameraBlockPos) + CameraOffset;
    pos.y += getWaveOffset();
    gl_Position = ProjMat * ModelViewMat * vec4(pos, 1.0);

    sphericalVertexDistance = fog_spherical_distance(pos);
    cylindricalVertexDistance = fog_cylindrical_distance(pos);
    
    vec4 vc = Color * minecraft_sample_lightmap(Sampler2, UV2);

    vec3 n = normalize(Normal);
    
    const float BRIGHTNESS_TOP  = 1.75; 
    const float BRIGHTNESS_SIDE  = 1.75; 
    const float BRIGHTNESS_BOTTOM = 1.1; 
    bool isCubeFace = (
        abs(n.y) > 0.1 || 
        abs(n.x) > 0.1 || 
        abs(n.z) > 0.1  
    );

    if (isCubeFace) {
        float faceShade = BRIGHTNESS_TOP; 

        if (n.y > 0.5) {
            faceShade = BRIGHTNESS_TOP;
        }
        else if (n.y < -0.5) {
            faceShade = BRIGHTNESS_BOTTOM;
        }
        else if (abs(n.x) > 0.5 || abs(n.z) > 0.5) {
            faceShade = BRIGHTNESS_SIDE;
        }

        vc.rgb *= faceShade;
    }

    #moj_import <no_light_no_color.glsl>

    vertexColor = vc;
    texCoord0 = UV0;
}

//by DR7 https://modrinth.com/user/DR7