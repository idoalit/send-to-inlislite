<?php

namespace Idoalit\S2i\Libs;

use Idoalit\S2i\Models\AuthHeader;
use Idoalit\S2i\Models\Catalog;
use Idoalit\SlimsEloquentModels\Author;
use Idoalit\SlimsEloquentModels\BiblioAuthor;
use Idoalit\SlimsEloquentModels\BiblioTopic;
use Idoalit\SlimsEloquentModels\Topic;

class Helper
{
    public static function getControlNumber($formatid)
    {
        $controlNumber = '';
        $newControlNumber = '';
        $branchCode_Controlnum = '';
        $digitFormat_Controlnum = '';

        $branchCode_Controlnum = 'INLIS';
        $digitFormat_Controlnum = 15;

        if ($formatid == 2) {
            $branchCode_Controlnum = "AUTH";
            $authHeader = AuthHeader::orderBy('ID', 'DESC')->first();
            $controlNumber = $authHeader->ID ?? null;
        } else {
            $controlNumber = Catalog::where('ControlNumber', 'LIKE', $branchCode_Controlnum . '%')->orderBy('ControlNumber', 'DESC')->first();
            $controlNumber = $controlNumber->ControlNumber ?? null;
        }

        if ($controlNumber) {
            $controlNumber = (int)preg_replace('/[^0-9]/', '', $controlNumber);
        }

        $newControlNumber =  $branchCode_Controlnum . str_pad((int)$controlNumber + 1, $digitFormat_Controlnum, '0', STR_PAD_LEFT);

        return $newControlNumber;
    }

    public static function getBibId($formatid)
    {
        $bibCode = '';
        $digitFormat = '';
        $newId = '';
        $maxId = '';
        $bibCode_Auth = '';
        $digitFormat_Auth = '';
        $bibCode_Biblio = '';
        $digitFormat_Biblio = '';


        $bibCode_Auth = 'AUTH-';
        $digitFormat_Auth = 11;
        $bibCode_Biblio = '0010-';
        $digitFormat_Biblio = 6;

        $yearMonth =  date('my');
        if ($formatid == 2) {
            $bibCode = $bibCode_Auth;
            $digitFormat = $digitFormat_Auth;
            $authHeader = AuthHeader::orderBy('ID', 'DESC')->first();
            $maxId = $authHeader->ID;
        } else {
            $bibCode = $bibCode_Biblio;
            $digitFormat = $digitFormat_Biblio;
            $CodeLen = strlen($bibCode . (string)$yearMonth);
            $BibIdLen = $CodeLen + $digitFormat;
            $maxId = Catalog::selectRaw('SUBSTR(MAX(BIBID),"' . $bibCode . (string)$yearMonth . '") AS MaxBibId')
                ->where('BIBID', 'LIKE', $bibCode . (string)$yearMonth . '%')
                ->whereRaw('LENGTH(BIBID) = ? ', (string)$BibIdLen)->first();
            $maxId = $maxId->MaxBibId;
        }
        $maxId =  (int)$maxId + 1;
        $newId =  $bibCode . $yearMonth . str_pad($maxId, $digitFormat, '0', STR_PAD_LEFT);

        return $newId;
    }

    public static function getSLiMSBIBID($id) : string {
        return 'SLiMS' . str_pad($id, 10, '0', STR_PAD_LEFT);
    }

    public static function getAuthors($biblio_id) {
        $biblioAuthor = BiblioAuthor::where('biblio_id', $biblio_id)->get();
        $authorIds = $biblioAuthor->map(fn($b) => $b->author_id)->toArray();
        return Author::whereIn('author_id', $authorIds)->get();
    }

    public static function getTopics($biblio_id) {
        $biblioTopic = BiblioTopic::where('biblio_id', $biblio_id)->get();
        $topicIds = $biblioTopic->map(fn($b) => $b->topic_id)->toArray();
        return Topic::whereIn('topic_id', $topicIds)->get();
    }

    public static function getAuthorFromSession() {
        $ids = array_map(fn($a) => $a[0], $_SESSION['biblioAuthor'] ?? []);
        return Author::whereIn('author_id', $ids)->get();
    }

    public static function getAuthorFromSessionToString($data = null) {
        $authors = $data;
        if (empty($data)) $authors = self::getAuthorFromSession();
        $authors = $authors->map(fn($a) => $a->author_name)->toArray();
        return implode('; ', $authors);
    }

    public static function getTopicFromSession() {
        $ids = array_map(fn($t) => $t[0], $_SESSION['biblioTopic'] ?? []);
        return Topic::whereIn('topic_id', $ids)->get();
    }

    public static function getTopicFromSessionToString($data = null) {
        $topics = $data;
        if (empty($data)) $topics = self::getTopicFromSession();
        $topics = $topics->map(fn($t) => $t->topic)->toArray();
        return implode('; ', $topics);
    }
}
