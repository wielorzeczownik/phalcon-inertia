<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= $this->routes->script() ?>
    <?= $this->vite->tags('resources/js/app.js') ?>
    <?= $head ?>
</head>
<body>
    <?= $body ?>
</body>
</html>
