<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
</head>
<body>
    <h1>{{ $pageTitle }}</h1>

    <ul>
        @foreach ($allBooks as $book)
            <li>
                <strong>Название книги:</strong> {{ $book['title'] }}<br>
                <strong>Цена:</strong> {{ $book['price'] }}<br>
            </li>
        @endforeach
    </ul>
</body>
</html>
