<?php

namespace App\Http\Controllers;

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
use Illuminate\Http\Request;
use Biplane\YandexDirect\Api\V5\Contract\AdFieldEnum;
use Biplane\YandexDirect\Api\V5\Contract\AdsSelectionCriteria;
use Biplane\YandexDirect\Api\V5\Contract\GetAdsRequest;
use Biplane\YandexDirect\Api\V5\Contract\StateEnum;
use Biplane\YandexDirect\User;

class ApiDirectController extends Controller
{
    //
    private $user;



    private function Init()
    {
        $this->user = new User([
            'access_token' => env("APIDIRECT_TOKEN"),
            'login' => env("APIDIRECT_LOGIN"),
            'locale' => User::LOCALE_RU,
            'sandbox' => env("APIDIRECT_SANDBOX")
        ]);
    }

    public function main()
    {
        $this->Init();

        $criteria = CampaignsSelectionCriteria::create();
        $payload = GetCampaignsRequest::create()
            ->setSelectionCriteria($criteria)
            ->setFieldNames([
                CampaignFieldEnum::ID,
                CampaignFieldEnum::NAME
            ]);

        $response = $this->user->getCampaignsService()->get($payload);

        return view("api.index", ["result" => $response->getCampaigns()]);
    }

    public function campaingMain()
    {
        return view("api.campaing.index");
    }

    public function getDictionary($param)
    {
        $this->Init();

        switch(trim(strtolower($param))){
            default:
            case 'currencies':
                $dictioanryNames =  DictionaryNameEnum::CURRENCIES; break;
            case 'georegions':
                $dictioanryNames =  DictionaryNameEnum::GEO_REGIONS; break;
            case 'timezones':
                $dictioanryNames =  DictionaryNameEnum::TIME_ZONES; break;
            case 'constants':
                $dictioanryNames =  DictionaryNameEnum::CONSTANTS; break;
            case 'adcategories':
                $dictioanryNames =  DictionaryNameEnum::AD_CATEGORIES; break;
            case 'operationsystemversions':
                $dictioanryNames =  DictionaryNameEnum::OPERATION_SYSTEM_VERSIONS; break;
            case 'productivityassertions':
                $dictioanryNames =  DictionaryNameEnum::PRODUCTIVITY_ASSERTIONS; break;
            case 'interests':
                $dictioanryNames =  DictionaryNameEnum::INTERESTS; break;
        }


        $request = GetDictionariesRequest::create()
            ->setDictionaryNames([$dictioanryNames]);

        $response = $this->user->getDictionariesService()->get($request);

        return $response;
    }

    public function addGroup(Request $request){
        $this->Init();
        $campaingId = $request->get("campaingid");
        $regions = explode(',',implode(',',$request->get("regions")));//explode(',',$request->get("regions"));
       $item = AdGroupAddItem::create()
            ->setName($request->get("name"))
            ->setCampaignId($campaingId)
            ->setRegionIds($regions); //explode(',',$request->get("regions"))

        $request = AddAdGroupsRequest::create()
            ->setAdGroups([$item]);

        $response = $this->user->getAdGroupsService()->add($request);

        //echo var_dump($response);
        return redirect("/test/apidirect/groups/list/".$campaingId);

    }
    public function addCampain(Request $request)
    {
        $this->Init();
        $textCampaingStrategyAdd = TextCampaignStrategyAdd::create()
            ->setSearch(TextCampaignSearchStrategyAdd::create()
                ->setBiddingStrategyType(TextCampaignSearchStrategyTypeEnum::HIGHEST_POSITION)
            )
            ->setNetwork(TextCampaignNetworkStrategyAdd::create()
                ->setBiddingStrategyType(TextCampaignNetworkStrategyTypeEnum::MAXIMUM_COVERAGE));

        $textCampaingAddItem = TextCampaignAddItem::create()
            ->setBiddingStrategy($textCampaingStrategyAdd);

        $compainItem = CampaignAddItem::create()
            ->setName($request->get("campaing_name"))
            ->setStartDate(date("Y-m-d"))
            ->setTextCampaign($textCampaingAddItem);

        $criteria = AddCampaignsRequest::create()
            ->setCampaigns(array($compainItem));

        $response = $this->user->getCampaignsService()->add($criteria);

        return redirect("/test/apidirect");

    }

    public function removeCampain($id)
    {
        $this->Init();
        $criteria = IdsCriteria::create()
            ->setIds([$id]);
        $payload = DeleteCampaignsRequest::create()
            ->setSelectionCriteria($criteria);
        $response = $this->user->getCampaignsService()->delete($payload);
        return redirect("/test/apidirect");
    }

    public function getCampain($ids)
    {
        $this->Init();
        $campaignsSelectionCriteria = CampaignsSelectionCriteria::create()
            ->setIds([$ids]/*explode(",", $ids)*/);
        $request = GetCampaignsRequest::create()
            ->setSelectionCriteria($campaignsSelectionCriteria)
            ->setFieldNames([
                CampaignFieldEnum::ID,
                CampaignFieldEnum::NAME,
                CampaignFieldEnum::BLOCKED_IPS,
                CampaignFieldEnum::CLIENT_INFO,
                CampaignFieldEnum::START_DATE,
                CampaignFieldEnum::STATUS
                ]
            )
            ->setTextCampaignFieldNames([
                TextCampaignFieldEnum::RELEVANT_KEYWORDS
            ]);

        $response = $this->user->getCampaignsService()->get($request);
        return view("api.campaing.get",["result"=>$response]);

    }

    public function removeGroup($groupId,$campaingId)
    {
        $this->Init();
        $critearia = IdsCriteria::create()
            ->setIds([$groupId]);

        $request = DeleteAdGroupsRequest::create()
            ->setSelectionCriteria($critearia);

        $response = $this->user->getAdGroupsService()->delete($request);

        return redirect("/test/apidirect/groups/list/".$campaingId);

    }
    public function addGroupPage($id){
        return view("api.groups.add",[
            "campaingId"=>$id,
            "regions"=>$this->getDictionary("georegions")
        ]);
    }

    public function addKeywordsPage($groupId){
        return view("api.keywords.add",["groupId"=>$groupId]);
    }

    private function getKWList($groupId){
        $this->Init();
        $keywordsSelectionCriteria = KeywordsSelectionCriteria::create()
            ->setAdGroupIds([$groupId]);

        $request = GetKeywordsRequest::create()
            ->setSelectionCriteria($keywordsSelectionCriteria)
            ->setFieldNames([
                KeywordFieldEnum::ID,
                KeywordFieldEnum::KEYWORD,
                KeywordFieldEnum::PRODUCTIVITY,
                KeywordFieldEnum::BID,
                KeywordFieldEnum::CONTEXT_BID,
                KeywordFieldEnum::STATISTICS_SEARCH,
                KeywordFieldEnum::STATISTICS_NETWORK,


            ]);

        $response = $this->user->getKeywordsService()->get($request);
        return $response;
    }
    public function getKeywordsList($groupId)
    {
       $response = $this->getKWList($groupId);

        return view("api.keywords.list",[
            "result"=>$response,
            "groupId"=>$groupId,
            "regions"=>$this->getDictionary("georegions"),
            "wordstatlist"=>$this->getWordstatReportList()
        ]);
    }

    public function createNewWordstatReport(Request $request){
        $groupId = $request->get("groupId");
        $words =explode(',',$request->get("words")); //explode(',',$request->get("words"));
        $regions = explode(',',implode(',',$request->get("regions")));//explode(',',$request->get("regions"));
        $this->Init();

        $request = NewWordstatReportInfo::create()
            ->setGeoID($regions)
            ->setPhrases($words);

        $response = $this->user->getApiService()->createNewWordstatReport($request);

        return redirect("/test/apidirect/keywords/list/".$groupId);
    }

    public function getWordstatReport($id,$groupId){
        $this->Init();
        $response = $this->getKWList($groupId);
        return view("api.wordstat.index",[
            "result"=>$this->user->getApiService()->getWordstatReport($id),
            "keywords"=>$response,
            "groupId"=>$groupId,
            "bids"=>$this->getBidData($groupId)
        ]);
    }

    public function deleteWordstatReport($id,$groupId){
        $this->Init();
        $this->user->getApiService()->deleteWordstatReport($id);

       return redirect("/test/apidirect/keywords/list/".$groupId);
    }
    public function getWordstatReportList(){
        $this->Init();
        return $this->user->getApiService()->getWordstatReportList();
    }

    public function removeKeyword($keywordId,$groupId){

        $this->Init();
        $criteria = IdsCriteria::create()
            ->setIds([$keywordId]);


        $request = DeleteKeywordsRequest::create()
            ->setSelectionCriteria($criteria);

        $response = $this->user->getKeywordsService()->delete($request);

        return redirect("/test/apidirect/keywords/list/".$groupId);
    }
    public function addKeywords(Request $request){
        //
        $this->Init();

        $groupid = $request->get("groupId");

        if ($request->get("autotargeting"))
            $keyword = "---autotargeting";
        else
            $keyword = $request->get("keyword");

        $item = KeywordAddItem::create()
            ->setKeyword($keyword)
            ->setAdGroupId($request->get("groupId"));

        $request = AddKeywordsRequest::create()
            ->setKeywords([$item]);

        $response = $this->user->getKeywordsService()->add($request);

        return redirect("/test/apidirect/keywords/list/".$groupid);
    }

    private function getBidData($groupId){
        $this->Init();
        $bidsSelectionCriteria = BidsSelectionCriteria::create()
            ->setAdGroupIds([$groupId]);
        // ->setCampaignIds([])
        // ->setKeywordIds([]);
        $request = GetBidsRequest::create()
            ->setSelectionCriteria($bidsSelectionCriteria)
            ->setFieldNames([
                BidFieldEnum::KEYWORD_ID,
                BidFieldEnum::AUCTION_BIDS,
                BidFieldEnum::COMPETITORS_BIDS,
                BidFieldEnum::CONTEXT_BID,
                BidFieldEnum::CURRENT_SEARCH_PRICE,
                BidFieldEnum::MIN_SEARCH_PRICE,
                BidFieldEnum::SEARCH_PRICES
            ]);
        $response = $this->user->getBidsService()->get($request);
        return  $response;
    }
    public function getBids($groupId){
        $response = $this->getBidData($groupId);
        return view("api.bids.index",["result"=>$response]);


    }
    public function getCampaingGroups($ids){

        $this->Init();
        $groupsSelectionCriteria = AdGroupsSelectionCriteria::create()
            ->setCampaignIds([$ids]);


       $request =  GetAdGroupsRequest::create()
           ->setSelectionCriteria($groupsSelectionCriteria)
           ->setFieldNames([
               AdGroupFieldEnum::ID,
               AdGroupFieldEnum::NAME,
               AdGroupFieldEnum::REGION_IDS,
               AdGroupFieldEnum::RESTRICTED_REGION_IDS,
           ]);

        $response = $this->user->getAdGroupsService()->get($request);
        return view("api.campaing.list",[
            "result"=>$response,
            "regions"=>$this->getDictionary("georegions"),
            "campaingId"=>$ids
        ]);

    }
    public function removeCompain($id)
    {
    }





}
