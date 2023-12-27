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
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class PageController extends Controller
{
    protected $token;

    public function __construct()
    {
        $this->token = 'EAAFfK6VooAcBO1S3OZB371JfOri0FyJr7eQOTZCB4hEFsCgnDtOagjEB3JxZCZB0jTSSSktEBQfNmarurDi2f5nZCEYWJZBiD4qH4McQHlZBAXZC11uIcRT4Yx7vpGIDyEaVSU7oMvf87v1dIxgZBbuyJRC9byY45JGewCYZBaM6cpdln0zbtBE3DNP5fe5TGmtZABrUqwZD';
    }

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

    private function get($url, $params = null)
    {
        if (!str_contains($url, 'facebook.com')) {
            $url = 'https://graph.facebook.com/v18.0/' . $url;
        }
        if (!str_contains($url, 'access_token') && !isset($params['access_token'])) {
            $params['access_token'] = $this->token;
        }
        $response = Http::get($url, $params);
        return $response->json();
    }

    public function handleAirTable()
    {
        // $airTable = Airtable::table('ba_con_soi')->orderBy('STT')->get()->toArray();
        // return $airTable;
        // $airTableFirst = $airTable[0];
        // return $airTableFirst;
        // return Airtable::table('default')->patch(
        //     $airTableFirst['id'],
        //     [
        //         'Notes' => 'US'
        //     ]
        // );
        Config::set('airtable.key', 'patmDcPF72RmtXkJw.3f94c24051da1a22bd236277af1f5b71e6aa4f75288fa53f89e0a339b826b3dc');
        Config::set('airtable.base', 'app2DtrdCueQjHt12');
        Config::set('airtable.tables.default.name', 'tblb4VFyzu2NSiib7');
        $lead = [
            'page_id' => '150067171518245',
            'page_name' => 'Cafe Thị Trường & Đầu Tư',
            'camp_id' => '902259454624531',
            'camp_name' => 'MT5_LỌC TUỔI',
            'id' => '1090585539052625',
            'created_time' => '2023-12-25T14:14:38+0000',
            'full_name' => 'Dinhquangvinh Dinh',
            'bạn_thuộc_nhóm_tuổi_nào_bên_dưới:' => '25-55_tuổi',
            'phone_number' => '+84986750869',
            'email' => 'minhtanthuong2023@gmail.com'
        ];
        return Airtable::table('default')->create([
            'Name' => $lead['full_name'],
            'Phone' => $lead['phone_number'],
            'Created time' => $lead['created_time'],
            'Status' => 'Done',
            'Assignee' => [
                'email' => 'locvv@yopaz.vn',
                // Các thông tin khác nếu cần
            ]
        ]);
    }

    public function getLeads()
    {
        $lastTime = Carbon::now('UTC')->subMinutes(60)->toIso8601String();
        $countPage = 0;
        $leadsData = [];
        $linkNext = 'me/accounts';
        while ($linkNext) {
            $pageResponse = $this->get($linkNext);
            return $pageResponse;
            // dd($pageResponse);
            // $linkNext = null;
            $linkNext = isset($pageResponse['paging']['next']) ? $pageResponse['paging']['next'] : false;
            $pages = $pageResponse['data'];
            $countPage += count($pages);
            for ($i = 2; $i < count($pages); $i++) {
                $page = $pages[$i];
                if ($page['id'] == '150067171518245') {
                    $count = 0;
                    $linkFormNext = 'me';
                    $paramsForm = [
                        'fields' => 'leadgen_forms{name,created_time,id,leads}',
                        'access_token' => $page['access_token']
                    ];
                    // dd($page['access_token']);
                    while ($linkFormNext) {
                        $formResponse = $this->get($linkFormNext, $paramsForm);
                        $formData = isset($formResponse['leadgen_forms']) ? $formResponse['leadgen_forms']['data'] : $formResponse['data'];
                        $formPaging = isset($formResponse['leadgen_forms']) ? $formResponse['leadgen_forms']['paging'] : $formResponse['paging'];

                        foreach ($formData as $form) {
                            if (isset($form['leads'])) {
                                $leads = $form['leads']['data'];
                                $leadsPaging = $form['leads']['paging'];
                                $countLead = 0;
                                while (count($leads)) {
                                    for ($j = 0; $j < count($leads); $j++) {
                                        // $lead = $leads[$j];
                                        // if ($lastTime > $lead['created_time']) {
                                        //     break;
                                        // }
                                        // $info = [];
                                        // foreach ($lead['field_data'] as $data) {
                                        //     $info[$data['name']] = $data['values'][0];
                                        // }
                                        // $baseData = [
                                        //     'page_id' => $page['id'],
                                        //     'page_name' => $page['name'],
                                        //     'camp_id' => $form['id'],
                                        //     'camp_name' => $form['name'],
                                        //     'id' => $lead['id'],
                                        //     'created_time' => $lead['created_time']
                                        // ];
                                        // $leadsData[] = array_merge($baseData, $info);
                                        // dd($leadsData);

                                        $countLead++;
                                        Log::info($countLead);
                                    }

                                    if (isset($leadsPaging['next'])) {
                                        $leadResponse = $this->get($leadsPaging['next']);
                                        $leads = $leadResponse['data'];
                                        $leadsPaging = $leadResponse['paging'];
                                    } else {
                                        $leads = [];
                                    }
                                }
                            }
                        }

                        $linkFormNext = isset($formPaging['next']) ? $formPaging['next'] : false;
                        $paramsForm = null;
                    }
                    dd($count);
                }
                dd('done');
            }
        }
        // $countPage++;
        return $countPage;
    }
}
