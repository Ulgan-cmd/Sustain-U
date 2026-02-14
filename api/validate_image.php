<?php
header("Content-Type: application/json");

include "config.php";

if (!isset($_FILES['issue_image'])) {
    echo json_encode(["error" => "No image uploaded"]);
    exit;
}

$image = $_FILES['issue_image']['tmp_name'];
$imageData = file_get_contents($image);
$base64Image = base64_encode($imageData);

$prompt = "
You are an AI system validating campus environmental issues.

Check if this image shows a REAL environmental issue such as:
- water leakage
- waste spillage
- air pollution
- drainage problem
- infrastructure damage

Respond ONLY in JSON format:

{
  \"isValid\": true or false,
  \"reason\": \"short explanation\",
  \"confidence\": number (0-100)
}
";

$data = [
    "contents" => [
        [
            "parts" => [
                ["text" => $prompt],
                [
                    "inline_data" => [
                        "mime_type" => "image/jpeg",
                        "data" => $base64Image
                    ]
                ]
            ]
        ]
    ]
];

$url = "https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent?key=" . $GEMINI_API_KEY;

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(["error" => curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);

$result = json_decode($response, true);

if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    echo json_encode(["error" => "Invalid AI response"]);
    exit;
}

$aiText = $result['candidates'][0]['content']['parts'][0]['text'];

echo $aiText;
?>
