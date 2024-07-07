<?php

namespace Idoalit\S2i\Libs;

use Idoalit\S2i\Models\Member as InlisliteMember;
use Idoalit\SlimsEloquentModels\Member;

class MemberHelper
{
    public static function run(Member $member) {
        $criteria = ['MemberNo' => $member->member_id];
        $value = [
            'Fullname' => $member->member_name,
            'DateOfBirth' => $member->birth_date,
            'Address' => $member->member_mail_address,
            'AddressNow' => $member->member_address,
            'Phone' => $member->member_phone,
            'NoHp' => $member->member_phone,
            'InstitutionName' => $member->inst_name,
            'Sex_id' => $member->gender > 0 ? 1 : 2,
            'Email' => $member->member_email,
            'RegisterDate' => $member->register_date,
            'EndDate' => $member->expire_date,
            'StatusAnggota_id' => $member->is_pending > 0 ? 4 : 3,
            'CreateTerminal' => 'SLiMS',
            'UpdateTerminal' => 'SLiMS',
        ];
        InlisliteMember::updateOrCreate($criteria, $value);
    }
}
