<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\YandexApi;
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
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Biplane\YandexDirect\Api\V5\Contract\AdFieldEnum;
use Biplane\YandexDirect\Api\V5\Contract\AdsSelectionCriteria;
use Biplane\YandexDirect\Api\V5\Contract\GetAdsRequest;
use Biplane\YandexDirect\Api\V5\Contract\StateEnum;
use Biplane\YandexDirect\User;
use Mockery\Exception;


class ApiDirectController extends Controller
{

    //yandex api injection
    private $api;

    public function __construct(YandexApi $api)
    {
        $this->api = $api;
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
            }catch(ClientException $e){
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
        $regions = explode(',', implode(',', $request->get("regions")));//explode(',',$request->get("regions"));

        $request = $this->api->addGroup($campaingId, $regions);
        return redirect("/test/apidirect/groups/list/" . $campaingId);

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
        $keyword = $request->get("keyword");
        $isAutotargeting = $request->get("autotargeting") ? True : False;

        $request = $this->api->addKeywords($groupid, $keyword, $isAutotargeting);
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


}
