<?php

namespace App\Http\Controllers;

use App\Services\NewsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class NewsController extends Controller
{
    public function __construct(
        private NewsService $newsService
    ) {}

    public function index(Request $request)
    {
        $date = $request->get('date', Carbon::now()->format('d.m.Y'));
        $search = $request->get('search', '');

        return view('news.index', compact('date', 'search'));
    }

    public function getNews(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date_format:d.m.Y',
            'search' => 'nullable|string|max:255'
        ]);

        $date = $request->get('date');
        $search = $request->get('search', '');

        try {
            $news = $this->newsService->getNews($date);
            
            if (!empty($search)) {
                $news = $this->newsService->filterByTitle($news, $search);
            }

            $newsArray = array_map(fn($item) => $item->toArray(), $news);

            return response()->json([
                'success' => true,
                'data' => $newsArray,
                'count' => count($newsArray),
                'date' => $date,
                'search' => $search
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении новостей: ' . $e->getMessage()
            ], 500);
        }
    }
}