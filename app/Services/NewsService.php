<?php

namespace App\Services;

use App\DTO\News;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Log;

class NewsService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ]
        ]);
    }

    public function getNews(string $date): array
    {
        try {
            $formattedDate = $this->validateAndFormatDate($date);
            
            $url = "https://kaktus.media/?lable=8&date={$formattedDate}";
            
            Log::info("Fetching news from URL: {$url}");
            
            $response = $this->client->get($url);
            $html = (string) $response->getBody();

            return $this->parseNewsFromHtml($html, $formattedDate);
            
        } catch (RequestException $e) {
            Log::error("Error fetching news: " . $e->getMessage());
            return [];
        } catch (\Exception $e) {
            Log::error("Unexpected error: " . $e->getMessage());
            return [];
        }
    }


    private function validateAndFormatDate(string $date): string
    {
        try {
            $cleanDate = trim($date);
            
            $formats = ['d.m.Y', 'Y-m-d', 'd/m/Y', 'd-m-Y'];
            
            foreach ($formats as $format) {
                try {
                    $carbonDate = Carbon::createFromFormat($format, $cleanDate);
                    if ($carbonDate && $carbonDate->format($format) === $cleanDate) {
                        return $carbonDate->format('d.m.Y');
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
            
            return Carbon::now()->format('d.m.Y');
            
        } catch (\Exception $e) {
            return Carbon::now()->format('d.m.Y');
        }
    }

  
private function parseNewsFromHtml(string $html, string $date): array
{
    $crawler = new Crawler($html);
    $news = [];

    $crawler->filter('.Tag--article')->each(function (Crawler $node) use (&$news, $date) {
        $titleNode = $node->filter('a.ArticleItem--name');
        $title = $titleNode->count() > 0 ? trim($titleNode->text()) : 'Без заголовка';
        $link = $titleNode->count() > 0 ? $titleNode->attr('href') : '#';
        $image = $node->filter('img')->count() > 0 ? $node->filter('img')->attr('src') : null;

        $news[] = new News($date, $title, $link, $image);
    });

    return $news;
}

    public function filterByTitle(array $newsList, string $search): array
{
        \Log::info("Фильтрация на бэке по: {$search}");

    $search = trim($search);

    if ($search === '') {
        return $newsList;
    }

    return array_values(array_filter($newsList, function (News $news) use ($search) {
        if (!isset($news->title) || !is_string($news->title)) {
            return false;
        }

        return stripos($news->title, $search) !== false;
    }));
}

}