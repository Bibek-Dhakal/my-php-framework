<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload</title>
</head>
<body>
    <h2>Upload a File</h2>
    <form action="upload1_action.php" method="post" enctype="multipart/form-data">
        <input type="file" name="file[]" multiple>
        <input type="file" name="gg[]" multiple>
        <input type="submit" value="Upload">
    </form>
</body>
</html>

