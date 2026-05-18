<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <script type="text/javascript" src="//code.jquery.com/jquery-3.6.0.min.js"></script>
        <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" />
        <script type="text/javascript" src="//cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

        <link href="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote.min.js"></script>
    </head>
    <body>
        <script>
        $(document).ready(function() {
            $('#summernote').summernote();
        });
        </script>
        <form method="post" action="/test">
            <textarea id="summernote" name="text">Hello Summernote</textarea>
            <input type="submit" value="Submit"></input>
        </form>
    </body>
</html>
