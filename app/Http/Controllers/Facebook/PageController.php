<?php

namespace App\Http\Controllers\Facebook;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class PageController extends Controller
{
    public function postsDetail($pageId)
    {
        $response = $this->get($pageId . '/feed');
        $posts = $response->data;
        $metaData = [];
        for ($i = 0; $i < 5; $i++) {
            $post = $posts[$i];

            $fields = '';
            $fields .= 'likes.summary(true),';
            $fields .= 'reactions.summary(true),';
            $fields .= 'shares.summary(true),';
            $fields .= 'comments.summary(true),';
            $fields = substr($fields, 0, strlen($fields) - 1);

            $response = $this->get($post['id'], ['fields' => $fields]);
            $postDetail = [
                'content' => $post['message'],
                'reactions' => $response->reactions['summary']['total_count'],
                'shares' => isset($response->shares) ? $response->shares['count'] : 0,
                'comments' => $response->comments['summary']['total_count']
            ];

            $response = $this->get($post['id'] . '/insights/post_impressions_unique,post_engaged_users');
            $postDetail['reach'] = $response->data[0]['values'][0]['value'];
            $postDetail['engagement'] = $response->data[1]['values'][0]['value'];

            $postDetail['created_time'] = Carbon::parse($post['created_time'])->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y');

            $metaData[$post['id']] = $postDetail;
            // return $postDetail;
        }

        return $metaData;
    }

    private function get($path, $params = [])
    {
        $params['access_token'] = 'EAAFfK6VooAcBOZBcblNfR98TSzEcPrGKvFsyrVwE5SwLLi6H6Qhy4tT1dblGLPQadaNQOrAafSg0r5Eo4SGTomHF663F3uQGZBhUkSZBZAZCnpanlx1KXf7xQHm4zhEwWZCO6kY815K1aSZC8bOktSwfZBbKtB4RSsF10ZCWtWXD44M6rX7idAS2ZAcMHHxV0vDccZD';
        $response = Http::get('https://graph.facebook.com/v18.0/' . $path, $params);
        return (object) $response->json();
    }
}
