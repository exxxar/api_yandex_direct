<?php
/**
 * Created by PhpStorm.
 * User: exxxa
 * Date: 22.11.2017
 * Time: 16:49
 */

namespace App\Http\Controllers\API;

use Biplane\YandexDirect\Api\V4\Contract\KeywordsSuggestionInfo;
use Biplane\YandexDirect\Api\V4\Contract\NewForecastInfo;
use Biplane\YandexDirect\Api\V4\Contract\NewWordstatReportInfo;
use Biplane\YandexDirect\Api\V4\Contract\WordstatReportInfo;
use Biplane\YandexDirect\Api\V5\Contract\AdAddItem;
use Biplane\YandexDirect\Api\V5\Contract\AddAdGroupsRequest;
use Biplane\YandexDirect\Api\V5\Contract\AddAdsRequest;
use Biplane\YandexDirect\Api\V5\Contract\AddCampaignsRequest;
use Biplane\YandexDirect\Api\V5\Contract\AddKeywordsRequest;
use Biplane\YandexDirect\Api\V5\Contract\AdGroupAddItem;
use Biplane\YandexDirect\Api\V5\Contract\AdGroupFieldEnum;
use Biplane\YandexDirect\Api\V5\Contract\AdGroupsSelectionCriteria;
use Biplane\YandexDirect\Api\V5\Contract\BidFieldEnum;
use Biplane\YandexDirect\Api\V5\Contract\BidSetAutoItem;
use Biplane\YandexDirect\Api\V5\Contract\BidSetItem;
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
use Biplane\YandexDirect\Api\V5\Contract\SetAutoBidsRequest;
use Biplane\YandexDirect\Api\V5\Contract\SetBidsRequest;
use Biplane\YandexDirect\Api\V5\Contract\TextAdAdd;
use Biplane\YandexDirect\Api\V5\Contract\TextCampaignAddItem;
use Biplane\YandexDirect\Api\V5\Contract\TextCampaignFieldEnum;
use Biplane\YandexDirect\Api\V5\Contract\TextCampaignNetworkStrategyAdd;
use Biplane\YandexDirect\Api\V5\Contract\TextCampaignNetworkStrategyTypeEnum;
use Biplane\YandexDirect\Api\V5\Contract\TextCampaignSearchStrategyAdd;
use Biplane\YandexDirect\Api\V5\Contract\TextCampaignSearchStrategyTypeEnum;
use Biplane\YandexDirect\Api\V5\Contract\TextCampaignStrategyAdd;
use Biplane\YandexDirect\Api\V5\Dictionaries;
use Exception;
use Illuminate\Http\Request;
use Biplane\YandexDirect\Api\V5\Contract\AdFieldEnum;
use Biplane\YandexDirect\Api\V5\Contract\AdsSelectionCriteria;
use Biplane\YandexDirect\Api\V5\Contract\GetAdsRequest;
use Biplane\YandexDirect\Api\V5\Contract\StateEnum;
use Biplane\YandexDirect\User;

class YandexApi
{
    private $user;

    public function __construct()
    {
        $this->Init();
    }

    private function Init()
    {
        $this->user = new User([
            'access_token' => env("APIDIRECT_TOKEN"),
            'login' => env("APIDIRECT_LOGIN"),
            'locale' => User::LOCALE_RU,
            'sandbox' => env("APIDIRECT_SANDBOX")
        ]);
    }

    public function getCampaingAll()
    {
        $criteria = CampaignsSelectionCriteria::create();
        $payload = GetCampaignsRequest::create()
            ->setSelectionCriteria($criteria)
            ->setFieldNames([
                CampaignFieldEnum::ID,
                CampaignFieldEnum::NAME
            ]);

        return $this->user->getCampaignsService()->get($payload);
    }


    public function getDictionaryAll($param)
    {
        switch (trim(strtolower($param))) {
            default:
            case 'currencies':
                $dictioanryNames = DictionaryNameEnum::CURRENCIES;
                break;
            case 'georegions':
                $dictioanryNames = DictionaryNameEnum::GEO_REGIONS;
                break;
            case 'timezones':
                $dictioanryNames = DictionaryNameEnum::TIME_ZONES;
                break;
            case 'constants':
                $dictioanryNames = DictionaryNameEnum::CONSTANTS;
                break;
            case 'adcategories':
                $dictioanryNames = DictionaryNameEnum::AD_CATEGORIES;
                break;
            case 'operationsystemversions':
                $dictioanryNames = DictionaryNameEnum::OPERATION_SYSTEM_VERSIONS;
                break;
            case 'productivityassertions':
                $dictioanryNames = DictionaryNameEnum::PRODUCTIVITY_ASSERTIONS;
                break;
            case 'interests':
                $dictioanryNames = DictionaryNameEnum::INTERESTS;
                break;
        }

        $request = GetDictionariesRequest::create()
            ->setDictionaryNames([$dictioanryNames]);
        return $this->user->getDictionariesService()->get($request);
    }

    public function addGroup($name, $campaingId, $regions = [1])
    {
        $item = AdGroupAddItem::create()
            ->setName($name)
            ->setCampaignId(floatval($campaingId))
            ->setRegionIds($regions);

        $request = AddAdGroupsRequest::create()
            ->setAdGroups([$item]);

        return $this->user->getAdGroupsService()->add($request);
    }

    public function createAds($adGroupId, $title = "some test title", $text = "some test text", $url = "http://mail.ru")
    {

        $textAdAdd = TextAdAdd::create()
            ->setTitle($title)
            ->setText($text)
            ->setHref($url)
            ->setMobile("NO");

        $adAddItem = AdAddItem::create()
            ->setAdGroupId(floatval($adGroupId))
            ->setTextAd($textAdAdd);

        $addAdsRequest = AddAdsRequest::create()
            ->setAds([$adAddItem]);

        return $this->user->getAdsService()->add($addAdsRequest);

    }

    public function addCampain($campaing_name)
    {
        $textCampaingStrategyAdd = TextCampaignStrategyAdd::create()
            ->setSearch(TextCampaignSearchStrategyAdd::create()
                ->setBiddingStrategyType(TextCampaignSearchStrategyTypeEnum::HIGHEST_POSITION)

            )
            ->setNetwork(TextCampaignNetworkStrategyAdd::create()
                ->setBiddingStrategyType(TextCampaignNetworkStrategyTypeEnum::MAXIMUM_COVERAGE));

        $textCampaingAddItem = TextCampaignAddItem::create()
            ->setBiddingStrategy($textCampaingStrategyAdd);

        $compainItem = CampaignAddItem::create()
            ->setName($campaing_name)
            ->setStartDate(date("Y-m-d"))
            ->setTextCampaign($textCampaingAddItem);

        $criteria = AddCampaignsRequest::create()
            ->setCampaigns(array($compainItem));

        return $this->user->getCampaignsService()->add($criteria);
    }

    public function removeCampain($id)
    {
        $criteria = IdsCriteria::create()
            ->setIds([floatval($id)]);
        $payload = DeleteCampaignsRequest::create()
            ->setSelectionCriteria($criteria);
        return $this->user->getCampaignsService()->delete($payload);

    }

    public function getCampain($ids)
    {
        $campaignsSelectionCriteria = CampaignsSelectionCriteria::create()
            ->setIds([floatval($ids)]/*explode(",", $ids)*/);
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

        return $this->user->getCampaignsService()->get($request);
    }

    public function removeGroup($groupId)
    {
        $critearia = IdsCriteria::create()
            ->setIds([floatval($groupId)]);

        $request = DeleteAdGroupsRequest::create()
            ->setSelectionCriteria($critearia);
        return $this->user->getAdGroupsService()->delete($request);

    }


    public function getKeywordsList($groupId)
    {
        $keywordsSelectionCriteria = KeywordsSelectionCriteria::create()
            ->setAdGroupIds([floatval($groupId)]);

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
        return $this->user->getKeywordsService()->get($request);
    }

    public function createNewWordstatReport($regions, $words)
    {
        $request = NewWordstatReportInfo::create()
            ->setGeoID($regions)
            ->setPhrases($words);
        return $this->user->getApiService()->createNewWordstatReport($request);
    }


    public function deleteWordstatReport($id)
    {
        return $this->user->getApiService()->deleteWordstatReport(floatval($id));
    }

    public function getWordstatReportList()
    {
        return $this->user->getApiService()->getWordstatReportList();
    }

    public function getWordstatReport($id)
    {
        return $this->user->getApiService()->getWordstatReport(floatval($id));
    }

    public function removeKeyword($keywordId)
    {
        $criteria = IdsCriteria::create()
            ->setIds([floatval($keywordId)]);

        $request = DeleteKeywordsRequest::create()
            ->setSelectionCriteria($criteria);
        return $this->user->getKeywordsService()->delete($request);
    }

    public function addKeyword($groupId, $word, $isAutotargeting)
    {
        $item = $this->addKeyword_item(floatval($groupId), $word, $isAutotargeting);
        return $this->doKeywordRequest([$item]);
    }

    public function addKeyword_item($groupId, $word, $isAutotargeting)
    {
        $keyword = $isAutotargeting ? "---autotargeting" : $word;
        $item = KeywordAddItem::create()
            ->setKeyword($keyword)
            ->setAdGroupId(floatval($groupId));

        return $item;

    }

    public function doKeywordRequest($keywords)
    {

        $request = AddKeywordsRequest::create()
            ->setKeywords( $keywords);

        return $this->user->getKeywordsService()->add($request);

    }

    public function setAutoBids($keywordId)
    {

        $bidSetAutoItem = BidSetAutoItem::create()
            ->setKeywordId($keywordId);
        $request = SetAutoBidsRequest::create()
            ->setBids([$bidSetAutoItem]);
        return $this->user->getBidsService()->setAuto($request);
    }

    public function createBidsItem($keywordId){
        return BidSetItem::create()
            ->setKeywordId($keywordId);
    }

    public function doBidsRequest(array $bidsItemArray){
        $request = SetBidsRequest::create()
            ->setBids($bidsItemArray);
        return $this->user->getBidsService()->set($request);
    }
    public function setBids($keywordId)
    {

        $bidSetItem = BidSetItem::create()
            ->setKeywordId($keywordId);

        $request = SetBidsRequest::create()
            ->setBids([$bidSetItem]);
        return $this->user->getBidsService()->set($request);
    }


    public function getBidData($groupId)
    {
        $bidsSelectionCriteria = BidsSelectionCriteria::create()
            ->setAdGroupIds([floatval($groupId)]);

        $request = GetBidsRequest::create()
            ->setSelectionCriteria($bidsSelectionCriteria)
            ->setFieldNames([
                BidFieldEnum::KEYWORD_ID,
                BidFieldEnum::AUCTION_BIDS,
                BidFieldEnum::COMPETITORS_BIDS,
                BidFieldEnum::CONTEXT_BID,
                BidFieldEnum::CONTEXT_COVERAGE,
                BidFieldEnum::CURRENT_SEARCH_PRICE,
                BidFieldEnum::MIN_SEARCH_PRICE,
                BidFieldEnum::SEARCH_PRICES
            ]);
        return $this->user->getBidsService()->get($request);
    }

    public function getCampaingGroups($ids)
    {

        $groupsSelectionCriteria = AdGroupsSelectionCriteria::create()
            ->setCampaignIds([floatval($ids)]);

        $request = GetAdGroupsRequest::create()
            ->setSelectionCriteria($groupsSelectionCriteria)
            ->setFieldNames([
                AdGroupFieldEnum::ID,
                AdGroupFieldEnum::NAME,
                AdGroupFieldEnum::REGION_IDS,
                AdGroupFieldEnum::RESTRICTED_REGION_IDS,
            ]);

        return $this->user->getAdGroupsService()->get($request);
    }

    //получаем подсказки по словам
    public function getKeywordsSuggestion($keyword)
    {
        $keywordsSuggestionInfo = new KeywordsSuggestionInfo();
        $keyword = strpos($keyword, ",") != False ? explode(",", $keyword) : [$keyword];
        for ($i = 0; $i < count($keyword); $i++)
            $keyword[$i] = trim($keyword[$i]);
        $keywordsSuggestionInfo->setKeywords($keyword);

        return $this->user->getApiService()->getKeywordsSuggestion($keywordsSuggestionInfo);
    }

    public function createNewForecast($keyword, $regions)
    {
        $newForecastInfo = new NewForecastInfo();
        if (!is_array($keyword))
            $keyword = strpos($keyword, ",") != False ? explode(",", $keyword) : [$keyword];

        for ($i = 0; $i < count($keyword); $i++)
            $keyword[$i] = trim($keyword[$i]);

        $newForecastInfo->setPhrases($keyword);
        $newForecastInfo->setGeoID($regions);
        $newForecastInfo->setAuctionBids("Yes");


        return $this->user->getApiService()->createNewForecast($newForecastInfo);
    }

    public function getForecastInfo($forecastId)
    {
        return $this->user->getApiService()->getForecast(floatval($forecastId));
    }

    public function getForecastList()
    {
        return $this->user->getApiService()->getForecastList();
    }

    public function deleteForecastReport($forecastId)
    {
        return $this->user->getApiService()->deleteForecastReport(floatval($forecastId));
    }

}