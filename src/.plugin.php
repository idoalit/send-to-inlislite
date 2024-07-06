<?php

/**
 * Plugin Name: Database Connector - Inlislite
 * Plugin URI: https://github.com/slims/slims9_bulian
 * Description: Use the simplicity of SLiMS to input data into Inlislite
 * Version: 0.0.1
 * Author: Ido Alit
 * Author URI: https://github.com/idoalit
 */

use Idoalit\ConnectorInlislite\Libs\Helper;
use Idoalit\ConnectorInlislite\Models\Catalog;
use Idoalit\ConnectorInlislite\Models\CatalogRuas;
use Idoalit\ConnectorInlislite\Models\CatalogSubRuas;
use Idoalit\ConnectorInlislite\Models\Worksheet;
use Idoalit\SlimsEloquentModels\Place;
use Idoalit\SlimsEloquentModels\Publisher;
use SLiMS\Plugins;

require_once __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/db.php';

Plugins::hook(Plugins::BIBLIOGRAPHY_AFTER_SAVE, function ($biblio) {
    // initial variable
    $datenow = date('ymd');
    $authors = Helper::getAuthorFromSession();
    $subjects = Helper::getTopicFromSession();

    // selected workshet = Monograf
    $worksheet = Worksheet::where('Name', 'Monograf')->first();

    // save to catalog
    $catalog = new Catalog();
    $catalog->ControlNumber = Helper::getControlNumber($worksheet->Format_id);
    $catalog->BIBID = Helper::getBibId($worksheet->Format_id);
    $catalog->Title = $biblio['title'] . ' / ' . $biblio['sor'];
    $catalog->Author = Helper::getAuthorFromSessionToString($authors);
    $catalog->Edition = $biblio['edition'];
    $catalog->Publisher = (Publisher::find($biblio['publisher_id']))->publisher_name . ', ';
    $catalog->PublishLocation = (Place::find($biblio['publish_place_id']))->place_name . ' : ';
    $catalog->PublishYear = $biblio['publish_year'];
    $catalog->Publikasi = $catalog->PublishLocation . $catalog->Publisher . $catalog->PublishYear;
    $catalog->Subject = Helper::getTopicFromSessionToString($subjects);
    $catalog->PhysicalDescription = $biblio['collation'];
    $catalog->ISBN = $biblio['isbn_issn'];
    $catalog->CallNumber = $biblio['call_number'];
    $catalog->Note = $biblio['notes'];
    $catalog->Languages = $biblio['language_id'];
    $catalog->DeweyNo = $biblio['classification'];
    $catalog->IsOPAC = (int)$biblio['opac_hide'];
    $catalog->Worksheet_id = $worksheet->ID;
    $catalog->CreateTerminal = 'SLiMS';
    $catalog->CreateBy = 33;
    $catalog->UpdateBy = 33;
    $catalog->save();


    // save to catalog ruas
    $sec = 0;
    foreach ($worksheet->fields as $field) {
        dump($field);
        $sec++;

        // initialize ruas
        $ruas = new CatalogRuas();
        $ruas->CatalogId = $catalog->ID;
        $ruas->Tag = $field->Tag;
        $ruas->CreateTerminal = 'SLiMS';
        $ruas->CreateBy = 33;
        $ruas->UpdateBy = 33;

        // initialize sub ruas
        $subRuas = new CatalogSubRuas();
        $subRuas->Sequence = 1;
        $subRuas->CreateBy = 33;
        $subRuas->CreateTerminal = 'SLiMS';

        switch ($field->Tag) {
            case '001':
                $ruas->Indicator1 = null;
                $ruas->Indicator2 = null;
                $ruas->Value = $catalog->ControlNumber;
                $ruas->Sequence = $sec;
                $ruas->save();
                break;
                
            case '005':
                $ruas->Indicator1 = null;
                $ruas->Indicator2 = null;
                $ruas->Value = date('Ymdhis');
                $ruas->Sequence = $sec;
                $ruas->save();
                break;
                
            case '035':
                $ruas->Indicator1 = '#';
                $ruas->Indicator2 = '#';
                $ruas->Value = '$a ' . $catalog->BIBID;
                $ruas->Sequence = $sec;
                $ruas->save();

                $subRuas->RuasID = $ruas->ID;
                $subRuas->SubRuas = 'a';
                $subRuas->Value = $catalog->BIBID;
                $subRuas->save();
                break;
                
            case '007':
                $ruas->Indicator1 = null;
                $ruas->Indicator2 = null;
                $ruas->Value = 'ta';
                $ruas->Sequence = $sec;
                break;
                
            case '008':
                $ruas->Indicator1 = null;
                $ruas->Indicator2 = null;
                $ruas->Value = $datenow . '###########################0#'.str_pad($biblio['language_id'], 5, '#');
                $ruas->Sequence = $sec;
                $ruas->save();

                $subRuas->RuasID = $ruas->ID;
                $subRuas->SubRuas = '4';
                $subRuas->Value = $ruas->Value;
                $subRuas->save();
                break;
                
            case '020':
                $ruas->Indicator1 = '#';
                $ruas->Indicator2 = '#';
                $ruas->Value = '$a ' . $catalog->ISBN;
                $ruas->Sequence = $sec;
                $ruas->save();

                $subRuas->RuasID = $ruas->ID;
                $subRuas->SubRuas = 'a';
                $subRuas->Value = $catalog->ISBN;
                $subRuas->save();
                break;
                
            case '082':
                $ruas->Indicator1 = '#';
                $ruas->Indicator2 = '#';
                $ruas->Value = '$a ' . $catalog->DeweyNo;
                $ruas->Sequence = $sec;
                $ruas->save();

                $subRuas->RuasID = $ruas->ID;
                $subRuas->SubRuas = 'a';
                $subRuas->Value = $catalog->DeweyNo;
                $subRuas->save();
                break;
                
            case '084':
                $ruas->Indicator1 = '#';
                $ruas->Indicator2 = '#';
                $ruas->Value = '$a ' . $catalog->CallNumber;
                $ruas->Sequence = $sec;
                $ruas->save();

                $subRuas->RuasID = $ruas->ID;
                $subRuas->SubRuas = 'a';
                $subRuas->Value = $catalog->CallNumber;
                $subRuas->save();
                break;
                
            case '100':
                $author_name = '';
                $author = $authors[0] ?? false;
                if ($author) $author_name = $author->author_name;

                $ruas->Indicator1 = 0;
                $ruas->Indicator2 = '#';
                $ruas->Value = '$a ' . $author_name;
                $ruas->Sequence = $sec;
                $ruas->save();

                $subRuas->RuasID = $ruas->ID;
                $subRuas->SubRuas = 'a';
                $subRuas->Value = $author_name;
                $subRuas->save();
                break;
                
            case '245':
                $titles = explode(':', $biblio['title']);
                $ruas->Indicator1 = '1';
                $ruas->Indicator2 = '#';
                $ruas->Value = '$a '.trim($titles[0] ?? '').' : $b '.trim($titles[1] ?? '').' /$c ' . $biblio['sor'];
                $ruas->Sequence = $sec;
                $ruas->save();

                foreach(explode('$', $ruas->Value) as $i => $value) { 
                    $subRuas = new CatalogSubRuas();
                    $subRuas->Sequence = $i+1;
                    $subRuas->CreateBy = 33;
                    $subRuas->CreateTerminal = 'SLiMS';
                    $subRuas->RuasID = $ruas->ID;
                    $subRuas->SubRuas = $value[0] ?? '';
                    $subRuas->Value = trim(substr($value, 1));
                    $subRuas->save();
                }

                break;
                
            case '250':
                $ruas->Indicator1 = '#';
                $ruas->Indicator2 = '#';
                $ruas->Value = '$a ' . $biblio['edition'];
                $ruas->Sequence = $sec;
                $ruas->save();

                $subRuas->RuasID = $ruas->ID;
                $subRuas->SubRuas = 'a';
                $subRuas->Value = $biblio['edition'];
                $subRuas->save();
                break;
                
            case '260':
                $ruas->Indicator1 = '#';
                $ruas->Indicator2 = '#';
                $ruas->Value = '$a '.$catalog->PublishLocation.' :$b '.$catalog->Publisher.',$c ' . $catalog->PublishYear;
                $ruas->Sequence = $sec;
                $ruas->save();

                foreach(explode('$', $ruas->Value) as $i => $value) { 
                    $subRuas = new CatalogSubRuas();
                    $subRuas->Sequence = $i+1;
                    $subRuas->CreateBy = 33;
                    $subRuas->CreateTerminal = 'SLiMS';
                    $subRuas->RuasID = $ruas->ID;
                    $subRuas->SubRuas = $value[0] ?? '';
                    $subRuas->Value = trim(substr($value, 1));
                    $subRuas->save();
                }
                break;
                
            case '300':
                $collations = explode(':', $biblio['collation']);
                $illustration = explode(';', $collations[1] ?? '');
                $ruas->Indicator1 = '#';
                $ruas->Indicator2 = '#';
                $ruas->Value = '$a '.trim($collations[0] ?? '').' : $b '.trim($illustration[0] ?? '').' ; $c ' . trim($illustration[1] ?? '');
                $ruas->Sequence = $sec;
                $ruas->save();

                foreach(explode('$', $ruas->Value) as $i => $value) { 
                    $subRuas = new CatalogSubRuas();
                    $subRuas->Sequence = $i+1;
                    $subRuas->CreateBy = 33;
                    $subRuas->CreateTerminal = 'SLiMS';
                    $subRuas->RuasID = $ruas->ID;
                    $subRuas->SubRuas = $value[0] ?? '';
                    $subRuas->Value = trim(substr($value, 1));
                    $subRuas->save();
                }
                break;
                
            case '700':
                if (!count($authors) > 1) {
                    foreach ($authors as $n => $a) {
                        if ($n < 1) continue;
                        $ruas_author = new CatalogRuas();
                        $ruas_author->CatalogId = $catalog->ID;
                        $ruas_author->Tag = $field->Tag;
                        $ruas_author->CreateTerminal = 'SLiMS';
                        $ruas_author->CreateBy = 33;
                        $ruas_author->UpdateBy = 33;
                        $ruas_author->Indicator1 = 0;
                        $ruas_author->Indicator2 = '#';
                        $ruas_author->Value = '$a ' . $a->author_name;
                        $ruas_author->Sequence = $sec;
                        $ruas_author->save();
                        $sec++;

                        $subRuas = new CatalogSubRuas();
                        $subRuas->Sequence = 1;
                        $subRuas->CreateBy = 33;
                        $subRuas->CreateTerminal = 'SLiMS';
                        $subRuas->RuasID = $ruas_author->ID;
                        $subRuas->SubRuas = 'a';
                        $subRuas->Value = $a->author_name;
                        $subRuas->save();
                    }
                }
                break;
                
            case '520':
                $ruas->Indicator1 = '#';
                $ruas->Indicator2 = '#';
                $ruas->Value = '$a ' . $biblio['notes'];
                $ruas->Sequence = $sec;
                $ruas->save();

                $subRuas->RuasID = $ruas->ID;
                $subRuas->SubRuas = 'a';
                $subRuas->Value = $biblio['edition'];
                $subRuas->save();
                break;
                
            case '600':
                if (!count($subjects) > 1) {
                    foreach ($subjects as $n => $t) {
                        if ($n < 1) continue;
                        $ruas_topic = new CatalogRuas();
                        $ruas_topic->CatalogId = $catalog->ID;
                        $ruas_topic->Tag = $field->Tag;
                        $ruas_topic->CreateTerminal = 'SLiMS';
                        $ruas_topic->CreateBy = 33;
                        $ruas_topic->UpdateBy = 33;
                        $ruas_topic->Indicator1 = '#';
                        $ruas_topic->Indicator2 = 4;
                        $ruas_topic->Value = '$a ' . $t->topic;
                        $ruas_topic->Sequence = $sec;
                        $ruas_topic->save();
                        $sec++;

                        $subRuas = new CatalogSubRuas();
                        $subRuas->Sequence = 1;
                        $subRuas->CreateBy = 33;
                        $subRuas->CreateTerminal = 'SLiMS';
                        $subRuas->RuasID = $ruas_topic->ID;
                        $subRuas->SubRuas = 'a';
                        $subRuas->Value = $t->topic;
                        $subRuas->save();
                    }
                }
                break;

        }
    }

});

Plugins::menu('opac', 'test', __DIR__ . '/test.php');
