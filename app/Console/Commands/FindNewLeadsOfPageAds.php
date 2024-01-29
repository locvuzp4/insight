<?php

namespace App\Console\Commands;

use Airtable;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FindNewLeadsOfPageAds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fa:leads';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected $token;

    public function __construct()
    {
        parent::__construct();
        $this->token = 'EAAFfK6VooAcBO1S3OZB371JfOri0FyJr7eQOTZCB4hEFsCgnDtOagjEB3JxZCZB0jTSSSktEBQfNmarurDi2f5nZCEYWJZBiD4qH4McQHlZBAXZC11uIcRT4Yx7vpGIDyEaVSU7oMvf87v1dIxgZBbuyJRC9byY45JGewCYZBaM6cpdln0zbtBE3DNP5fe5TGmtZABrUqwZD';
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $lastTime = Carbon::now('UTC')->subMinutes(120)->toIso8601String();

        $countPage = 0;
        $totalLeads = 0;
        $leadsData = [];
        $linkNext = 'me/accounts';

        while ($linkNext) {
            $pageResponse = $this->get($linkNext);
            // $linkNext = null;
            $linkNext = isset($pageResponse['paging']['next']) ? $pageResponse['paging']['next'] : false;
            $pages = $pageResponse['data'];
            for ($i = 0; $i < count($pages); $i++) {
                $countPage++;
                Log::info('page: ' . $countPage);
                $page = $pages[$i];

                $linkFormNext = 'me';
                $paramsForm = [
                    'fields' => 'leadgen_forms{name,created_time,id,leads}',
                    'access_token' => $page['access_token']
                ];

                $countForm = 0;
                while ($linkFormNext) {
                    $formResponse = $this->get($linkFormNext, $paramsForm);
                    $formData = [];
                    if (isset($formResponse['leadgen_forms'])) {
                        $formData = $formResponse['leadgen_forms']['data'];
                        $formPaging = $formResponse['leadgen_forms']['paging'];
                    } elseif (isset($formResponse['data'])) {
                        $formData = $formResponse['data'];
                        $formPaging = $formResponse['paging'];
                    }

                    foreach ($formData as $form) {
                        $countForm++;
                        Log::info('form: ' . $countForm);

                        if (isset($form['leads'])) {
                            $leads = $form['leads']['data'];
                            $leadsPaging = $form['leads']['paging'];
                            $countLead = 0;
                            while (count($leads)) {
                                for ($j = 0; $j < count($leads); $j++) {
                                    $lead = $leads[$j];
                                    if ($lastTime > $lead['created_time']) {
                                        break;
                                    }
                                    $info = [];
                                    foreach ($lead['field_data'] as $data) {
                                        $info[$data['name']] = $data['values'][0];
                                    }
                                    $baseData = [
                                        'page_id' => $page['id'],
                                        'page_name' => $page['name'],
                                        'form_id' => $form['id'],
                                        'form_name' => $form['name'],
                                        'id' => $lead['id'],
                                        'created_time' => $lead['created_time']
                                    ];
                                    $leadsData[] = array_merge($baseData, $info);

                                    $countLead++;
                                    $totalLeads++;
                                    Log::info('lead: ' . $countLead);
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
            }
        }

        Config::set('airtable.key', 'patmDcPF72RmtXkJw.3f94c24051da1a22bd236277af1f5b71e6aa4f75288fa53f89e0a339b826b3dc');
        Config::set('airtable.base', 'app2DtrdCueQjHt12');
        Config::set('airtable.tables.default.name', 'tblb4VFyzu2NSiib7');
        foreach ($leadsData as $lead) {
            $dataCreate = [
                'Created time' => $lead['created_time'],
                'Status' => 'Done',
                'Assignee' => [
                    'email' => 'locvv@yopaz.vn',
                ]
            ];
            if (isset($lead['full_name'])) {
                $dataCreate['Name'] = $lead['full_name'];
            }
            if (isset($lead['phone_number'])) {
                $dataCreate['Phone'] = $lead['phone_number'];
            }

            Airtable::table('default')->create($dataCreate);
        }
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
}
