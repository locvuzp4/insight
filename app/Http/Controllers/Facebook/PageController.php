<?php

namespace App\Http\Controllers\Facebook;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use App\Helpers\GoogleSheet;
use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_ValueRange;
use Airtable;

class PageController extends Controller
{
    public function postsDetail($pageId)
    {
        $response = $this->get('me', [
            'fields' => 'id,name'
        ]);
        $response = $this->get($pageId . '/feed');

        // $countPost = count($response->data);
        // while (isset($response->paging['next'])) {
        //     $linkNext = $response->paging['next'];
        //     $responseRR = Http::get($linkNext);
        //     $response = (object) $responseRR->json();
        //     $countPost += count($response->data);
        // }
        // return $countPost;

        $posts = $response->data;
        $metaData = [];
        // for ($i = 0; $i < count($posts); $i++) {
        for ($i = 0; $i < 1; $i++) {
            $post = $posts[$i];
            // return $post;

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

            // $response = $this->get($post['id'] . '/insights/post_impressions_unique,post_engaged_users');

            $response = $this->get($post['id'] . '/insights', [
                'metric' => 'post_impressions_unique,post_engaged_users'
            ]);

            $postDetail['reach'] = $response->data[0]['values'][0]['value'];
            $postDetail['engagement'] = $response->data[1]['values'][0]['value'];

            $postDetail['created_time'] = Carbon::parse($post['created_time'])->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y');

            $metaData[$post['id']] = $postDetail;
            // return $postDetail;
        }
        $airTableData = [];
        $stt = 1;
        foreach ($metaData as $postId => $postDetail) {
            $airTableData[] = [
                'STT' => $stt,
                'Post ID' => $postId,
                'Post' => $postDetail['content'],
                'Reach' => $postDetail['reach'],
                'Engagements' => $postDetail['engagement'],
                'React' => $postDetail['reactions'],
                'Comment' => $postDetail['comments'],
                'Share' => $postDetail['shares'],
                'Ngày đăng bài' => $postDetail['created_time'],
            ];
            $stt++;
        }
        // dd($airTableData);
        // return $metaData;
        return Airtable::table('ba_con_soi')->patch([
            [
                'fields' => ['Post' => 'US2']
            ]
        ]);
    }

    private function get($path, $params = [])
    {
        $params['access_token'] = 'EAAFfK6VooAcBOZBcblNfR98TSzEcPrGKvFsyrVwE5SwLLi6H6Qhy4tT1dblGLPQadaNQOrAafSg0r5Eo4SGTomHF663F3uQGZBhUkSZBZAZCnpanlx1KXf7xQHm4zhEwWZCO6kY815K1aSZC8bOktSwfZBbKtB4RSsF10ZCWtWXD44M6rX7idAS2ZAcMHHxV0vDccZD';
        $response = Http::get('https://graph.facebook.com/v18.0/' . $path, $params);
        return (object) $response->json();
    }

    public function handleAirTable()
    {
        $airTable = Airtable::table('ba_con_soi')->orderBy('STT')->get()->toArray();
        return $airTable;
        // $airTableFirst = $airTable[0];
        // return $airTableFirst;
        // return Airtable::table('default')->patch(
        //     $airTableFirst['id'],
        //     [
        //         'Notes' => 'US'
        //     ]
        // );
        return Airtable::table('default')->patch([
            [
                'id' => $airTableFirst['id'],
                'fields' => ['Notes' => 'US2']
            ]
        ]);
    }
}
