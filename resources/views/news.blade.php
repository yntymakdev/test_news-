<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Новости Kaktus</title>
    <style>
        body { font-family: Arial; background: #f7f7f7; }
        .container { max-width: 900px; margin: 30px auto; padding: 20px; background: white; border-radius: 10px; }
        .news-item { margin-bottom: 20px; border-bottom: 1px solid #ccc; padding-bottom: 15px; }
        .news-item img { max-width: 100%; height: auto; }
        form { margin-bottom: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Новости за {{ $date }}</h1>

        <form method="GET" action="{{ url('/news') }}">
            <label>Дата:
                <input type="date" name="date" value="{{ \Carbon\Carbon::createFromFormat('d.m.Y', $date)->format('Y-m-d') }}">
            </label>
            <label>Поиск:
                <input type="text" name="search" value="{{ $search }}">
            </label>
            <button type="submit">Фильтр</button>
        </form>

        @forelse ($news as $item)
            <div class="news-item">
                <h3><a href="{{ $item->url }}" target="_blank">{{ $item->title }}</a></h3>
                @if($item->image)
                    <img src="{{ $item->image }}" alt="Картинка">
                @endif
                <p>Дата: {{ $item->date }}</p>
            </div>
        @empty
            <p>Нет новостей за выбранную дату.</p>
        @endforelse
    </div>
</body>
</html>
