<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_ValueRange;
use Illuminate\Support\Facades\Http;

class GetPostDetail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $token;

    protected $spreadSheetId;

    /**
     * Create a new job instance.
     */
    public function __construct($token, $spreadSheetId)
    {
        $this->token = $token;
        $this->spreadSheetId = $spreadSheetId;
    }

    public function handle(): void
    {
        $page = $this->get('me', [
            'fields' => 'id,name'
        ]);
        $postNumber = 1;
        $metaData = [];

        $pageResponse = $this->get($page->id . '/feed');
        $posts = $pageResponse->data;
        for ($i = 0; $i < count($posts); $i++) {
            $post = $posts[$i];
            dump('get post ' . $postNumber . ': ' . $post['id']);
            $postNumber++;
            $metaData[$post['id']] = $this->getPostDetail($post);
        }

        while (isset($pageResponse->paging['next'])) {
            $linkNext = $pageResponse->paging['next'];
            $responseNext = Http::get($linkNext);
            $pageResponse = (object) $responseNext->json();

            $posts = $pageResponse->data;
            for ($i = 0; $i < count($posts); $i++) {
                $post = $posts[$i];
                dump('get post ' . $postNumber . ': ' . $post['id']);
                $postNumber++;
                $metaData[$post['id']] = $this->getPostDetail($post);
            }
        }

        dump('start update sheet data');
        $client = $this->getGoogleClient();
        $service = new Google_Service_Sheets($client);
        $range = $page->name . '!A1:I';

        $hearder = [
            'STT',
            'Post ID',
            'Post',
            'Reach',
            'Engagements',
            'React',
            'Comment',
            'Share',
            'Ngày đăng bài'
        ];
        $body = [];

        $stt = 1;
        foreach ($metaData as $postId => $postDetail) {
            $body[] = [
                $stt,
                $postId,
                $postDetail['content'],
                $postDetail['reach'],
                $postDetail['engagement'],
                $postDetail['reactions'],
                $postDetail['comments'],
                $postDetail['shares'],
                $postDetail['created_time'],
            ];
            $stt++;
        }

        $data = array_merge([$hearder], $body);

        $requestBody = new Google_Service_Sheets_ValueRange([
            'values' => $data
        ]);

        $params = [
            'valueInputOption' => 'RAW'
        ];

        $service->spreadsheets_values->update($this->spreadSheetId, $range, $requestBody, $params);
        dump('update sheet data success');
        // return $metaData;
    }

    private function getPostDetail($post)
    {
        $fields = '';
        $fields .= 'likes.summary(true),';
        $fields .= 'reactions.summary(true),';
        $fields .= 'shares.summary(true),';
        $fields .= 'comments.summary(true),';
        $fields = substr($fields, 0, strlen($fields) - 1);

        $response = $this->get($post['id'], ['fields' => $fields]);
        $postDetail = [
            'content' => isset($post['message']) ? $post['message'] : $post['story'],
            'reactions' => $response->reactions['summary']['total_count'],
            'shares' => isset($response->shares) ? $response->shares['count'] : 0,
            'comments' => $response->comments['summary']['total_count']
        ];

        $response = $this->get($post['id'] . '/insights', [
            'metric' => 'post_impressions_unique,post_engaged_users'
        ]);

        if (isset($response->data[0])) {
            $postDetail['reach'] = $response->data[0]['values'][0]['value'];
        } else {
            $postDetail['reach'] = 'không có dữ liệu';
        }
        if (isset($response->data[1])) {
            $postDetail['engagement'] = $response->data[1]['values'][0]['value'];
        } else {
            $postDetail['engagement'] = 'không có dữ liệu';
        }

        $postDetail['created_time'] = Carbon::parse($post['created_time'])->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y');

        return $postDetail;
    }

    private function get($path, $params = [])
    {
        $params['access_token'] = $this->token;
        $response = Http::get('https://graph.facebook.com/v18.0/' . $path, $params);
        return (object) $response->json();
    }

    public function getGoogleClient()
    {
        $client = new Google_Client();
        $client->setApplicationName('Google Sheets API PHP Quickstart');
        $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
        $client->setAuthConfig(config_path('credentials_mina.json'));
        $client->setAccessType('offline');

        $tokenPath = storage_path('app/token.json');
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }

        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } else {
                $authUrl = $client->createAuthUrl();
                printf("Open the following link in your browser:\n%s\n", $authUrl);
                print 'Enter verification code: ';
                $authCode = trim(fgets(STDIN));

                // Exchange authorization code for an access token.
                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                $client->setAccessToken($accessToken);

                // Check to see if there was an error.
                if (array_key_exists('error', $accessToken)) {
                    throw new \Exception(join(', ', $accessToken));
                }
            }

            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        }

        return $client;
    }
}
