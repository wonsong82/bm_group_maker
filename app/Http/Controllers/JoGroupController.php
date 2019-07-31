<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class JoGroupController extends Controller
{

    public function index()
    {
        return view('upload');
    }




    protected function makeAgeGroups($users, $count)
    {
        $totalGroupCount = (int)request()->get('group');

        $groupCount = floor($totalGroupCount / $count);
        $groupRemainder = $totalGroupCount % $count;
        if($groupRemainder > 0){
            $remainderStartIndex = floor(($count - $groupRemainder)/2);
            $remainderEndIndex = $remainderStartIndex + ($groupRemainder-1);
        }
        else {
            $remainderStartIndex = 99999999;
            $remainderEndIndex = -1;
        }

        $users = $users->sortBy('year')->split($totalGroupCount);

        $ageGroups = collect();
        for($i=0; $i<$count; $i++){
            $ageGroups[] = collect([
                'groupCount' => $i>=$remainderStartIndex && $i<=$remainderEndIndex ? $groupCount+1 : $groupCount
            ]);
        }

        $start=0;
        $ageGroups = $ageGroups->map(function($ageGroup) use ($users, &$start){
            $ageGroup['users'] = collect();
            for($i=0; $i<$ageGroup['groupCount']; $i++){
                $ageGroup['users'] = $ageGroup['users']->merge($users[$start + $i]);
            }

            $start += $ageGroup['groupCount'];

            $ageGroup['diff'] = $ageGroup['users']->max('year') - $ageGroup['users']->min('year');

            return $ageGroup;
        });


        return $ageGroups;
    }


    public function makeGroup()
    {
        $file = request()->all()['file'];

        $excel = Excel::selectSheets('attendence')->load($file)->get();

        $users = collect();
        foreach($excel as $user){
            $users[] = collect([
                'name' => trim($user['name']),
                'gender' => trim(strtolower($user['gender'])) == 'm' ? Gender::MALE : Gender::FEMALE,
                'dob' => $user['dob']->format('Y/m/d'),
                'year' => (int)$user['dob']->format('Y'),
                'phone' => $this->parsePhone($user['phone']),
                'info' => trim($user['info']),
                'level' => $this->parseLevel($user['level']),
                'fortlee' => trim($user['fortlee']) == 'Y' ? true : false,
                'admin' => trim($user['admin']) == 'Y' ? true: false,
            ]);
        }

        // filter out
        $fortleeUsers = $users->filter(function($user){
            return !!$user['fortlee'] && !$user['admin'];
        });

        $users = $users->filter(function($user){
            return !$user['admin'] && !$user['fortlee'];
        });



        $ageGroups = $this->makeAgeGroups($users, 4);


        // Group settings
        $trial = 0;
        $maxTrial = 100000;


        $finalGroups = collect();

        foreach($ageGroups as $ageGroup){

            // Make Group
            do {
                $trial++;

                if($trial == $maxTrial){
                    exit('Max trial of ' . number_format($maxTrial) . ' reached.');
                }

                $shuffledUsers = $ageGroup['users']->shuffle();
                $groups = $shuffledUsers->split($ageGroup['groupCount']);
            }
            while(!$this->validateGroups($groups, []));

            $finalGroups = $finalGroups->merge($groups);
        }


        $finalGroups[] = $fortleeUsers;


        $groups = $finalGroups->map(function($group){
            $group->ageGap = $group->max('year') - $group->min('year');
            $group->levelL = $group->where('level', Level::L)->count();
            $group->levelA = $group->where('level', Level::A)->count();
            $group->levelB = $group->where('level', Level::B)->count();
            $group->levelC = $group->where('level', Level::C)->count();
            $group->levelD = $group->where('level', Level::D)->count();
            $group->male = $group->where('gender', Gender::MALE)->count();
            $group->female = $group->where('gender', Gender::FEMALE)->count();
            return $group;
        });




        return view('result', compact('trial', 'groups'));
    }

    protected function validateGroups($groups, $options)
    {
        $level = null;
        $gender = null;

        foreach($groups as $group){
            if(!$gender){
                $gender = [
                    Gender::MALE => $group->where('gender', Gender::MALE)->count(),
                    Gender::FEMALE => $group->where('gender', Gender::FEMALE)->count(),
                ];
            }
            else {
                if(abs($group->where('gender', Gender::MALE)->count() - $gender[Gender::MALE]) > 1)
                    return false;
                if(abs($group->where('gender', Gender::FEMALE)->count() - $gender[Gender::FEMALE]) > 1)
                    return false;
            }

            if(!$level){
                $level = [
                    Level::L => $group->where('level', Level::L)->count(),
                    Level::A => $group->where('level', Level::A)->count(),
                    Level::B => $group->where('level', Level::B)->count(),
                    Level::C => $group->where('level', Level::C)->count(),
                    Level::D => $group->where('level', Level::D)->count(),
                ];
            }
            else {
                if(abs($group->where('level', Level::L)->count() - $level[Level::L]) > 1)
                    return false;
                if(abs($group->where('level', Level::A)->count() - $level[Level::A]) > 1)
                    return false;
                if(abs($group->where('level', Level::B)->count() - $level[Level::B]) > 1)
                    return false;
                if(abs($group->where('level', Level::C)->count() - $level[Level::C]) > 1)
                    return false;
                if(abs($group->where('level', Level::D)->count() - $level[Level::D]) > 1)
                    return false;
            }
        }

        return true;
    }


    protected function parsePhone($string)
    {
        if(!$string) return '';

        $d = preg_replace('#[^\d]#', '', $string);
        if(strlen($d) > 10)
            $d = substr($d, strlen($d) - 10, 10);

        return sprintf('(%s%s%s) %s%s%s-%s%s%s%s', $d[0], $d[1], $d[2], $d[3], $d[4], $d[5], $d[6], $d[7], $d[8], $d[9]);
    }

    protected function parseLevel($string)
    {
        switch(strtoupper(trim($string))){
            case 'L':
                return Level::L;
            case 'A':
                return Level::A;
            case 'B':
                return Level::B;
            case 'C':
                return Level::C;
            case 'D':
                return Level::D;
        }
    }

}

class Gender {
    const MALE      = 'M';
    const FEMALE    = 'F';
}

class Level {
    const L     = 'L';
    const A     = 'A';
    const B     = 'B';
    const C     = 'C';
    const D     = 'D';
}
