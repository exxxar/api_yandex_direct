<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\YandexApi;
use App\Keywords;
use Barryvdh\DomPDF\PDF;
use Biplane\YandexDirect\Api\V4\Contract\NewWordstatReportInfo;
use Biplane\YandexDirect\Api\V4\Contract\WordstatReportInfo;
use Biplane\YandexDirect\Api\V5\Contract\AddAdGroupsRequest;
use Biplane\YandexDirect\Api\V5\Contract\AddCampaignsRequest;
use Biplane\YandexDirect\Api\V5\Contract\AddKeywordsRequest;
use Biplane\YandexDirect\Api\V5\Contract\AdGroupAddItem;
use Biplane\YandexDirect\Api\V5\Contract\AdGroupFieldEnum;
use Biplane\YandexDirect\Api\V5\Contract\AdGroupsSelectionCriteria;
use Biplane\YandexDirect\Api\V5\Contract\BidFieldEnum;
use Biplane\YandexDirect\Api\V5\Contract\BidsSelectionCriteria;
use Biplane\YandexDirect\Api\V5\Contract\CampaignAddItem;
use Biplane\YandexDirect\Api\V5\Contract\CampaignFieldEnum;
use Biplane\YandexDirect\Api\V5\Contract\CampaignGetItem;
use Biplane\YandexDirect\Api\V5\Contract\CampaignsSelectionCriteria;
use Biplane\YandexDirect\Api\V5\Contract\DeleteAdGroupsRequest;
use Biplane\YandexDirect\Api\V5\Contract\DeleteCampaignsRequest;
use Biplane\YandexDirect\Api\V5\Contract\DeleteKeywordsRequest;
use Biplane\YandexDirect\Api\V5\Contract\DictionaryNameEnum;
use Biplane\YandexDirect\Api\V5\Contract\DynamicTextCampaignFieldEnum;
use Biplane\YandexDirect\Api\V5\Contract\GetAdGroupsRequest;
use Biplane\YandexDirect\Api\V5\Contract\GetBidsRequest;
use Biplane\YandexDirect\Api\V5\Contract\GetCampaignsRequest;
use Biplane\YandexDirect\Api\V5\Contract\GetDictionariesRequest;
use Biplane\YandexDirect\Api\V5\Contract\GetKeywordsRequest;
use Biplane\YandexDirect\Api\V5\Contract\IdsCriteria;
use Biplane\YandexDirect\Api\V5\Contract\KeywordAddItem;
use Biplane\YandexDirect\Api\V5\Contract\KeywordFieldEnum;
use Biplane\YandexDirect\Api\V5\Contract\KeywordsSelectionCriteria;
use Biplane\YandexDirect\Api\V5\Contract\TextCampaignAddItem;
use Biplane\YandexDirect\Api\V5\Contract\TextCampaignFieldEnum;
use Biplane\YandexDirect\Api\V5\Contract\TextCampaignNetworkStrategyAdd;
use Biplane\YandexDirect\Api\V5\Contract\TextCampaignNetworkStrategyTypeEnum;
use Biplane\YandexDirect\Api\V5\Contract\TextCampaignSearchStrategyAdd;
use Biplane\YandexDirect\Api\V5\Contract\TextCampaignSearchStrategyTypeEnum;
use Biplane\YandexDirect\Api\V5\Contract\TextCampaignStrategyAdd;
use Biplane\YandexDirect\Api\V5\Dictionaries;
use Biplane\YandexDirect\Exception\ApiException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Biplane\YandexDirect\Api\V5\Contract\AdFieldEnum;
use Biplane\YandexDirect\Api\V5\Contract\AdsSelectionCriteria;
use Biplane\YandexDirect\Api\V5\Contract\GetAdsRequest;
use Biplane\YandexDirect\Api\V5\Contract\StateEnum;
use Biplane\YandexDirect\User;
use Illuminate\Support\Facades\DB;
use Mockery\Exception;


class ApiDirectController extends Controller
{

    //yandex api injection
    private $api;
    private $pdf;

    public function __construct(YandexApi $api, PDF $pdf)
    {
        $this->api = $api;
        $this->pdf = $pdf;
    }

    public function getCode($code = "")
    {

        $client_id = env('APIDIRECT_CLIENT_ID');
        $client_secret = env('APIDIRECT_CLIENT_SECRET');

        if (!empty($code)) {

            try {
                $client = new Client();

                $result = $client->request('POST', 'https://oauth.yandex.ru/token', [
                    'form_params' => [
                        'grant_type' => 'authorization_code',
                        'code' => $code,
                        'client_id' => $client_id,
                        'client_secret' => $client_secret
                    ],
                    'headers' => [
                        "Content-type", "application/x-www-form-urlencoded"
                    ],

                ]);


                return view("api.token", [
                    "token" => json_decode($result->getBody()->getContents())->access_token
                ]);
            } catch (ClientException $e) {
                return view("api.token", [
                    "error" => $e,
                    "link" => 'https://oauth.yandex.ru/authorize?response_type=code&client_id=' . $client_id
                ]);
            }

        } else {
            return view("api.token", [
                "link" => 'https://oauth.yandex.ru/authorize?response_type=code&client_id=' . $client_id
            ]);
        }

    }


    public function main()
    {
        $response = $this->api->getCampaingAll();
        return view("api.index", ["result" => $response->getCampaigns()]);
    }

    public function campaingMain()
    {
        return view("api.campaing.index");
    }

    public function addGroup(Request $request)
    {

        $campaingId = $request->get("campaingid");
        $groupName =$request->get("name");
        $regions = explode(',', implode(',', $request->get("regions")));//explode(',',$request->get("regions"));

        $request = $this->api->addGroup($groupName,$campaingId, $regions);

        echo  $request->getAddResults()[0]->getId();
        $rez =  $this->api->createAds($request->getAddResults()[0]->getId());
        echo var_dump($rez);
        //return redirect("/test/apidirect/groups/list/" . $campaingId);

    }

    public function addCampain(Request $request)
    {
        $response = $this->api->addCampain($request->get("campaing_name"));
        return redirect("/test/apidirect");
    }

    public function removeCampain($id)
    {
        $response = $this->api->removeCampain($id);
        return redirect("/test/apidirect");
    }

    public function getCampain($ids)
    {
        $response = $this->api->getCampain($ids);
        return view("api.campaing.get", ["result" => $response]);
    }

    public function removeGroup($groupId, $campaingId)
    {
        $response = $this->api->removeGroup($groupId);
        return redirect("/test/apidirect/groups/list/" . $campaingId);
    }

    public function addGroupPage($id)
    {
        return view("api.groups.add", [
            "campaingId" => $id,
            "regions" => $this->getDictionary("georegions")
        ]);
    }

    public function addKeywordsPage($groupId)
    {
        return view("api.keywords.add", ["groupId" => $groupId]);
    }


    public function getKeywordsList($groupId)
    {
        $response = $this->api->getKeywordsList($groupId);

        return view("api.keywords.list", [
            "result" => $response,
            "groupId" => $groupId,
            "regions" => $this->api->getDictionaryAll("georegions"),
            "wordstatlist" => $this->api->getWordstatReportList()
        ]);
    }

    public function createNewWordstatReport(Request $request)
    {
        $groupId = $request->get("groupId");
        $words = explode(',', $request->get("words")); //explode(',',$request->get("words"));
        $regions = explode(',', implode(',', $request->get("regions")));//explode(',',$request->get("regions"));

        $response = $this->api->createNewWordstatReport($regions, $words);
        return redirect("/test/apidirect/keywords/list/" . $groupId);
    }

    public function getWordstatReport($id, $groupId)
    {

        $response = $this->api->getKeywordsList($groupId);
        return view("api.wordstat.index", [
            "result" => $this->api->getWordstatReport($id),
            "keywords" => $response,
            "groupId" => $groupId,
            "bids" => $this->api->getBidData($groupId)
        ]);
    }

    public function deleteWordstatReport($id, $groupId)
    {
        $response = $this->api->deleteWordstatReport($id);
        return redirect("/test/apidirect/keywords/list/" . $groupId);
    }

    public function getWordstatReportList()
    {
        return $this->api->getWordstatReportList();
    }

    public function removeKeyword($keywordId, $groupId)
    {

        $request = $this->api->removeKeyword($keywordId);

        return redirect("/test/apidirect/keywords/list/" . $groupId);
    }

    public function addKeywords(Request $request)
    {

        $groupid = $request->get("groupId");
        echo "[$groupid]";
        $keyword = $request->get("keyword");
        $isAutotargeting = $request->get("autotargeting") ? True : False;

        $request = $this->api->addKeyword($groupid, $keyword, $isAutotargeting);

        return redirect("/test/apidirect/keywords/list/" . $groupid);
    }

    public function getBids($groupId)
    {
        $response = $this->api->getBidData($groupId);
        return view("api.bids.index", ["result" => $response]);
    }

    public function getCampaingGroups($ids)
    {
        $response = $this->api->getCampaingGroups($ids);

        return view("api.campaing.list", [
            "result" => $response,
            "regions" => $this->api->getDictionaryAll("georegions"),
            "campaingId" => $ids
        ]);

    }

    public function generatePDF($groupId)
    {
        $keyword = Keywords::where('ad_group_id', "$groupId")->get();
        ini_set('max_execution_time', 30000);
        $pdf = $this->pdf->loadView('api.pdf.pdf', ["keywords" => $keyword]);
        return $pdf->download('invoice.pdf');
    }

    public function getPdfList()
    {
        $list = Keywords::select('ad_group_id', DB::raw('count(ad_group_id)'))
            ->groupBy('ad_group_id')
            ->havingRaw("count(ad_group_id) > 0")
            ->orderBy('count(ad_group_id)', 'asc')
            ->get();
        return view("api.pdf.list", ["result" => $list, "index" => 0]);
    }


    public function getSuggestionPage()
    {
        return view("api.suggestions.index");
    }

    public function getSuggestions(Request $request)
    {
        $words = $request->get("words");
        return $this->api->getKeywordsSuggestion($words);
    }

    public function forecastMain()
    {
        $result = $this->api->getForecastList();
        $region = $this->api->getDictionaryAll("georegions");
        return view("api.forecast.index", ["result" => $result, "regions" => $region]);
    }

    public function createForecast(Request $request)
    {
        $regions = explode(',', implode(',', $request->get("regions")));//explode(',',$request->get("regions"));

        $this->api->createNewForecast($request->get("words"), $regions);

        return redirect("/test/apidirect/forecast/");
    }

    public function getForecast($id)
    {
        try {
            $result = $this->api->getForecastInfo($id);
            return view("api.forecast.report", ["result" => $result]);
        } catch (ApiException $apiException) {
            return redirect("/test/apidirect/forecast/");
        }

    }

    public function removeForecast($id) {
        try {
            $result = $this->api->deleteForecastReport($id);
        } catch (ApiException $apiException) {

        }
        return redirect("/test/apidirect/forecast/");
    }
}
