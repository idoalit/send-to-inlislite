<?php

namespace Idoalit\S2i\Libs;

use Idoalit\S2i\Models\Catalog;
use Idoalit\S2i\Models\CatalogRuas;
use Idoalit\S2i\Models\CatalogSubRuas;
use Idoalit\S2i\Models\Worksheet;
use Idoalit\SlimsEloquentModels\Biblio;
use Idoalit\SlimsEloquentModels\Place;
use Idoalit\SlimsEloquentModels\Publisher;

class CatalogHelper
{
    protected $catalog = null;
    protected $worksheet = null;
    protected $biblio = null;
    private $sequence = 1;

    function __construct(Worksheet $worksheet, Biblio $biblio,)
    {
        $this->worksheet = $worksheet;
        $this->biblio = $biblio;
    }

    static function delete($biblio_id)
    {
        $catalogRuas = CatalogRuas::where('Tag', '035')
            ->where('Value', '$a ' . Helper::getSLiMSBIBID($biblio_id))
            ->first();

        $ruas = CatalogRuas::where('CatalogId', (int)$catalogRuas->CatalogId)->get();

        // delete sub ruas
        $ruas->each(fn ($r) => CatalogSubRuas::where('RuasID', (int)$r->ID)->delete());

        // delete ruas
        CatalogRuas::where('CatalogId', (int)$catalogRuas->CatalogId)->delete();

        // delete catalog
        Catalog::destroy((int)$catalogRuas->CatalogId);
    }

    function updateOrCreate($SLiMS_BIBID, $authors, $topics): Catalog
    {
        // check if existing data exist
        $catalogRuas = CatalogRuas::where('Tag', '035')
            ->where('Value', '$a ' . $SLiMS_BIBID)
            ->first();

        if (!$catalogRuas) {
            $this->catalog = new Catalog();
            $this->catalog->ControlNumber = Helper::getControlNumber($this->worksheet->Format_id);
            $this->catalog->BIBID = Helper::getBibId($this->worksheet->Format_id);
            $this->catalog->CreateTerminal = 'SLiMS';
            $this->catalog->CreateBy = 33;
        } else {
            $this->catalog = Catalog::find($catalogRuas->CatalogId);
        }

        // setup catalogue
        $this->catalog->Title = $this->biblio->title . ' / ' . $this->biblio->sor;
        $this->catalog->Author = $authors->implode('author_name', '; ');
        $this->catalog->Edition = $this->biblio->edition;
        $this->catalog->Publisher = (Publisher::find($this->biblio->publisher_id ?? null))->publisher_name ?? null;
        $this->catalog->PublishLocation = (Place::find($this->biblio->publish_place_id ?? null))->place_name ?? null;
        $this->catalog->PublishYear = $this->biblio->publish_year;
        $this->catalog->Publikasi = $this->catalog->PublishLocation . ' : ' . $this->catalog->Publisher . ', ' . $this->catalog->PublishYear;
        $this->catalog->Subject = $topics->implode('topic', '; ');
        $this->catalog->PhysicalDescription = $this->biblio->collation;
        $this->catalog->ISBN = $this->biblio->isbn_issn;
        $this->catalog->CallNumber = $this->biblio->call_number;
        $this->catalog->Note = $this->biblio->notes;
        $this->catalog->Languages = $this->biblio->language_id;
        $this->catalog->DeweyNo = $this->biblio->classification;
        $this->catalog->ApproveDateOPAC = null;
        $this->catalog->IsOPAC = !$this->biblio->opac_hide;
        $this->catalog->IsBNI = null;
        $this->catalog->IsKIN = null;
        $this->catalog->IsRDA = null;
        $this->catalog->CoverURL = null;
        $this->catalog->Branch_id = null;
        $this->catalog->Worksheet_id = $this->worksheet->ID;
        $this->catalog->UpdateTerminal = 'SLiMS';
        $this->catalog->UpdateBy = 33;
        $this->catalog->MARC_LOC = null;
        $this->catalog->PRESERVASI_ID = null;
        $this->catalog->QUARANTINEDBY = null;
        $this->catalog->QUARANTINEDDATE = null;
        $this->catalog->QUARANTINEDTERMINAL = null;
        $this->catalog->Member_id = null;
        $this->catalog->KIILastUploadDate = null;
        $this->catalog->save();

        return $this->catalog;
    }

    function updateOrCreateRuas($tag, $value, $indicator1 = '#', $indicator2 = '#', $multiple = false): CatalogRuas
    {
        $criteria = ['CatalogId' => $this->catalog->ID, 'Tag' => $tag, 'Indicator1' => $indicator1, 'Indicator2' => $indicator2];
        if ($multiple) $criteria['Value'] = $value;
        $ruas = CatalogRuas::updateOrCreate($criteria, ['Value' => $value, 'CreateTerminal' => 'SLiMS']);
        if ($ruas) $this->updateOrCreateSubRuas($ruas);
        return $ruas;
    }

    function updateOrCreateSubRuas(CatalogRuas $ruas)
    {
        $fields = $this->parseMarcSubfields($ruas->Value);
        foreach ($fields as $key => $field) {
            $subRuas = CatalogSubRuas::updateOrCreate(
                ['RuasID' => $ruas->ID, 'SubRuas' => $key],
                ['Value' => $field, 'Sequence' => $this->sequence]
            );
            if ($subRuas) $this->sequence++;
        }
    }

    function parseMarcSubfields($marcString)
    {
        $subfields = array();
        // Split the string by '$' and filter out empty parts
        $parts = array_filter(explode('$', $marcString));

        foreach ($parts as $index => $part) {
            if ($index === 0) continue;
            if (strlen($part) > 1) {
                $code = $part[0]; // The first character is the subfield code
                $value = substr($part, 1); // The rest is the subfield value
                $subfields[$code] = $value;
            }
        }

        return $subfields;
    }
}
