<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Новости Kaktus.media</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .news-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            transition: box-shadow 0.3s ease;
        }
        .news-item:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .news-image {
            max-width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 4px;
        }
        .news-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
            text-decoration: none;
            line-height: 1.4;
        }
        .news-title:hover {
            color: #007bff;
            text-decoration: underline;
        }
        .loading {
            text-align: center;
            padding: 40px;
        }
        .filters {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .no-results {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="row">
            <div class="col-12">
                <h1 class="text-center mb-4">Новости Kaktus.media</h1>
                
                <!-- Фильтры -->
                <div class="filters">
                    <div class="row">
                        <div class="col-md-4">
                            <label for="date-filter" class="form-label">Дата:</label>
                            <input type="text" 
                                   id="date-filter" 
                                   class="form-control" 
                                   placeholder="дд.мм.гггг"
                                   value="{{ $date }}"
                                   pattern="\d{2}\.\d{2}\.\d{4}">
                        </div>
                        <div class="col-md-6">
                            <label for="search-filter" class="form-label">Поиск по заголовку:</label>
                            <input type="text" 
                                   id="search-filter" 
                                   class="form-control" 
                                   placeholder="Введите текст для поиска..."
                                   value="{{ $search }}">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button id="search-btn" class="btn btn-primary w-100 mb-1">Найти</button>
                                <button id="backBtn" style="display: none;">Назад</button>

                        </div>
                    </div>
                    
                    <div class="row mt-2">
                        <div class="col-md-12">
                            
                            <button id="debug-btn" class="btn btn-warning btn-sm">Отладка HTML</button>
                        </div>
                    </div>
                </div>

                <div id="results-info" class="mb-3 text-muted"></div>
                
                <div id="loading" class="loading d-none">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Загрузка...</span>
                    </div>
                    <p class="mt-2">Загрузка новостей...</p>
                </div>

                <div id="news-container" class="row">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
     class NewsApp {
    constructor() {
        this.dateFilter = document.getElementById('date-filter');
        this.searchFilter = document.getElementById('search-filter');
        this.searchBtn = document.getElementById('search-btn');
        this.backBtn = document.getElementById('backBtn');
        this.debugBtn = document.getElementById('debug-btn');
        this.newsContainer = document.getElementById('news-container');
        this.loading = document.getElementById('loading');
        this.resultsInfo = document.getElementById('results-info');

        this.init();
    }

    init() {
        this.searchBtn.addEventListener('click', () => this.loadNews());
        this.backBtn.addEventListener('click', () => this.resetSearch());
        this.debugBtn.addEventListener('click', () => this.debugHtml());

        this.searchFilter.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.loadNews();
        });
        this.dateFilter.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.loadNews();
        });

        this.loadNews();
    }

    async loadNews() {
        const date = this.dateFilter.value;
        const search = this.searchFilter.value.trim();

        if (!this.validateDate(date)) {
            alert('Пожалуйста, введите дату в формате дд.мм.гггг');
            return;
        }

        this.showLoading();

        try {
            const params = new URLSearchParams({ date, search });
            const response = await fetch(`/api/news?${params}`);
            const data = await response.json();

            if (data.success) {
                this.renderNews(data.data);
                this.updateResultsInfo(data.count, data.date, data.search);
                this.backBtn.style.display = search ? 'inline-block' : 'none';
            } else {
                this.showError(data.message || 'Ошибка при загрузке новостей');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showError('Ошибка при загрузке новостей');
        } finally {
            this.hideLoading();
        }
    }

    resetSearch() {
        this.searchFilter.value = '';
        this.backBtn.style.display = 'none';
        this.loadNews();
    }

    validateDate(date) {
        const regex = /^\d{2}\.\d{2}\.\d{4}$/;
        return regex.test(date);
    }

    renderNews(news) {
        if (news.length === 0) {
            this.newsContainer.innerHTML = `
                <div class="col-12">
                    <div class="no-results">
                        <h3>Новости не найдены</h3>
                        <p>Попробуйте изменить дату или поисковый запрос</p>
                    </div>
                </div>
            `;
            return;
        }

        this.newsContainer.innerHTML = news.map(item => this.createNewsItem(item)).join('');
    }

    createNewsItem(news) {
        const imageHtml = news.image ? 
            `<img src="${news.image}" alt="Изображение новости" class="news-image mb-3" onerror="this.style.display='none'">` : 
            '';

        return `
            <div class="col-lg-6 col-md-12">
                <div class="news-item">
                    ${imageHtml}
                    <h3>
                        <a href="${news.url}" target="_blank" class="news-title">
                            ${this.escapeHtml(news.title)}
                        </a>
                    </h3>
                    <p class="text-muted mb-0">
                        <small>Дата: ${news.date}</small>
                    </p>
                </div>
            </div>
        `;
    }

    updateResultsInfo(count, date, search) {
        let info = `Найдено ${count} новостей за ${date}`;
        if (search) {
            info += ` по запросу "${this.escapeHtml(search)}"`;
        }
        this.resultsInfo.textContent = info;
    }

    showLoading() {
        this.loading.classList.remove('d-none');
        this.newsContainer.innerHTML = '';
        this.resultsInfo.textContent = '';
    }

    hideLoading() {
        this.loading.classList.add('d-none');
    }

    showError(message) {
        this.newsContainer.innerHTML = `
            <div class="col-12">
                <div class="alert alert-danger" role="alert">
                    ${message}
                </div>
            </div>
        `;
    }

    async debugHtml() {
        const date = this.dateFilter.value;
        
        if (!this.validateDate(date)) {
            alert('Пожалуйста, введите дату в формате дд.мм.гггг');
            return;
        }

        this.showLoading();

        try {
            const params = new URLSearchParams({ date });
            const response = await fetch(`/api/debug-html?${params}`);
            const data = await response.json();

            console.log('Debug HTML:', data);
            
            this.newsContainer.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-info">
                        <h4>Отладочная информация</h4>
                        <p><strong>URL:</strong> ${data.url || 'N/A'}</p>
                        <p><strong>Длина HTML:</strong> ${data.total_html_length || 'N/A'}</p>
                        <p><strong>Найденные контейнеры:</strong></p>
                        <pre>${JSON.stringify(data.found_containers, null, 2)}</pre>
                        <p><strong>Образец HTML:</strong></p>
                        <pre>${data.html_sample || 'N/A'}</pre>
                    </div>
                </div>
            `;
            
        } catch (error) {
            console.error('Debug Error:', error);
            this.showError('Ошибка при отладке HTML');
        } finally {
            this.hideLoading();
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new NewsApp();
});

        // Инициализация приложения
        document.addEventListener('DOMContentLoaded', () => {
            new NewsApp();
        });
        // @extends('layouts.app')

@section('content')
<div class="container">
    <input type="date" id="dateInput" value="{{ $date }}">
    <input type="text" id="searchInput" placeholder="Поиск..." value="{{ $search }}">
    <button id="searchBtn">Поиск</button>

    <div id="newsList" class="mt-4"></div>
</div>
@endsection

@section('scripts')
<script>
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    const backBtn = document.getElementById('backBtn');

    searchBtn.addEventListener('click', () => {
        const search = searchInput.value.trim();
        loadNews(search);

        if (search !== '') {
            backBtn.style.display = 'inline-block';
        }
    });

    backBtn.addEventListener('click', () => {
        searchInput.value = '';
        loadNews(); // без фильтра
        backBtn.style.display = 'none';
    });

    function loadNews(search = '') {
        const date = document.getElementById('dateInput')?.value || '';

        let url = `/api/news?date=${encodeURIComponent(date)}`;
        if (search) {
            url += `&search=${encodeURIComponent(search)}`;
        }

        fetch(url)
            .then(res => res.json())
            .then(data => {
                renderNews(data.data);
            })
            .catch(err => {
                console.error('Ошибка загрузки:', err);
            });
    }


  
</script>
// @endsection

    </script>
</body>
</html>