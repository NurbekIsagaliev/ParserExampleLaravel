<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\DB;

class WebScraperController extends Controller
{
    public function scrape()
    {
        $baseUrl = 'http://books.toscrape.com/';
        $client = Http::timeout(60);

        // Извлекаем заголовок страницы
    $response = $client->get($baseUrl);
    $crawler = new Crawler($response->body());
    $pageTitle = $crawler->filter('title')->text();

        // Определяем максимальное количество страниц
        $maxPages = $this->getMaxPages($client, $baseUrl);

        // Извлекаем информацию о каждой книге на всех страницах
        $allBooks = [];

        for ($currentPage = 1; $currentPage <= $maxPages; $currentPage++) {
            $url = $baseUrl . 'catalogue/page-' . $currentPage . '.html';

            try {
                // Добавляем задержку в 1 секунду между запросами
                sleep(1);

                // Устанавливаем User-Agent
                $response = $client->withHeaders(['User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36'])
                    ->get($url);

                $crawler = new Crawler($response->body());
            } catch (\Exception $e) {
                // Обрабатываем ошибку
                break;
            }

            // Проверяем наличие элементов на текущей странице
            $bookNodes = $crawler->filter('article.product_pod');

            if ($bookNodes->count() === 0) {
                break; // Если нет элементов на странице, выходим из цикла
            }

            // Извлекаем информацию о каждой книге на текущей странице
            $booksOnPage = $bookNodes->each(function ($node) {
                $bookTitle = $node->filter('h3 a')->text();
                $bookPrice = $node->filter('p.price_color')->text();

                return [
                    'title' => $bookTitle,
                    'price' => $bookPrice,
                ];
            });

            // Объединяем данные с текущей страницы с общим массивом
            $allBooks = array_merge($allBooks, $booksOnPage);
        }

        // Удаляем старые записи перед вставкой новых данных
        DB::table('books')->truncate();

        // Вставляем данные в базу данных
        foreach ($allBooks as $book) {
            DB::table('books')->insert([
                'title' => $book['title'],
                'price' => $book['price'],
            ]);
        }

        return view('welcome', compact('pageTitle', 'allBooks'));
    }


    private function getMaxPages($client, $baseUrl)
    {
        try {
            $response = $client->get($baseUrl);
            $crawler = new Crawler($response->body());

            // Попробуйте найти элемент с классом "current"
            $currentPageElement = $crawler->filter('li.current');

            // Если элемент найден, извлеките текст
            if ($currentPageElement->count() > 0) {
                $currentPageText = $currentPageElement->text();

                // Используйте регулярное выражение для извлечения числа страниц
                if (preg_match('/Page \d+ of (\d+)/', $currentPageText, $matches)) {
                    return (int)$matches[1];
                }
            }

            // Если не удалось найти информацию, вернуть 1
            return 1;
        } catch (\Exception $e) {
            return 1;
        }
    }
}
