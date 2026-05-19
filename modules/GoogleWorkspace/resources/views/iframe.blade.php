<!DOCTYPE html>
<html>
<head>
    <title>Google Doc Editor</title>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
        }
        iframe {
            width: 100%;
            height: 100vh;
            border: none;
        }
    </style>
</head>
<body>
    <iframe src="{{ $editUrl }}" allow="camera; microphone; fullscreen; display-capture"></iframe>
</body>
</html>
