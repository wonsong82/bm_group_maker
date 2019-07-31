<?php

namespace App\Http\Controllers;

use App\Result;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class GroupController extends Controller
{

    public function index()
    {
        $list = $this->getList();

        return view('upload', compact('list'));
    }


    public function load(Result $result)
    {
        $trial  = $result->trial;
        $groups = unserialize($result->groups);

        $list = $this->getList();
        $load = true;

        return view('result', compact('trial', 'groups', 'list', 'load'));
    }


    public function save()
    {
        $name = request()->get('name');
        $temp = Result::where('temp', true)->limit(1)->first();

        $result = Result::create([
            'name' => $name,
            'trial' => $temp->trial,
            'groups' => $temp->groups,
        ]);

        echo $result->id;
    }

    public function delete(Result $result)
    {
        $result->delete();
    }





    public function makeGroup()
    {
        $config = $this->getConfig();

        // get users from excel
        $file = request()->all()['file'];
        $excel = Excel::selectSheets('list')->load($file)->get();
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
                'leader' => trim($user['leader']) == 'Y' ? true : false,
                'admin' => trim($user['admin']) == 'Y' ? true: false,
            ]);
        }

        // filter out assignable users
        $users = $users->filter(function($user){
            return !$user['admin'] && !$user['leader'];
        });

        // make age groups
        $ageGroups = $this->makeAgeGroups($users, $config);

        // create groups
        $result      = $this->makeGroups($ageGroups, $config);
        $finalGroups = $result->finalGroup;
        $trial       = $result->trial;


        $groups = $finalGroups->map(function($group){
            $group->ageGap  = $group->max('year') - $group->min('year');
            $group->levelA  = $group->where('level', Level::A)->count();
            $group->levelB  = $group->where('level', Level::B)->count();
            $group->levelC  = $group->where('level', Level::C)->count();
            $group->levelD  = $group->where('level', Level::D)->count();
            $group->levelE  = $group->where('level', Level::E)->count();
            $group->male    = $group->where('gender', Gender::MALE)->count();
            $group->female  = $group->where('gender', Gender::FEMALE)->count();
            return $group;
        });


        // temporarily save
        Result::updateOrCreate([
            'temp' => true
        ], [
            'name' => 'Last created group (Temp saved)',
            'trial' => $trial,
            'groups' => serialize($groups),
            'temp' => true
        ]);


        $list = $this->getList();

        return view('result', compact('trial', 'groups', 'list'));
    }


    protected function getList()
    {
        $temp = Result::where('temp', true)->limit(1)->get(['id', 'name', 'updated_at', 'temp']);
        $list = Result::where('temp', false)->orderBy('created_at', 'desc')->get(['id', 'name', 'updated_at', 'temp']);

        return collect()->merge($temp)->merge($list);

    }

    protected function makeAgeGroups($users, $config)
    {
        $totalGroupCount    = (int)$config['num_of_groups'];
        $ageGroupCount      = (int)$config['num_of_age_groups'];

        $groupCount         = floor($totalGroupCount / $ageGroupCount); // how many in 1 group
        $groupRemainder     = $totalGroupCount % $ageGroupCount;

        if($groupRemainder > 0){
            $remainderStartIndex    = floor(($ageGroupCount - $groupRemainder) / 2);
            $remainderEndIndex      = $remainderStartIndex + ($groupRemainder - 1);
        }
        else {
            $remainderStartIndex    = 99999999;
            $remainderEndIndex      = -1;
        }

        $users = $users->sortBy('year')->split($totalGroupCount);


        $ageGroups = collect();
        for($i = 0; $i < $ageGroupCount; $i++){
            $ageGroups[] = collect([
                'groupCount' => $i >= $remainderStartIndex && $i <= $remainderEndIndex ? $groupCount + 1 : $groupCount
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

    protected function makeGroups($ageGroups, $config)
    {
        $trial = 0;
        $maxTrial = $config['max_trial'];

        // make group
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
            while(!$this->validateGroups($groups, $config));

            $finalGroups = $finalGroups->merge($groups);
        }

        return (object)['finalGroup' => $finalGroups, 'trial' => $trial];
    }


    protected function validateGroups($groups, $config)
    {
        $level = null;
        $gender = null;

        foreach($groups as $group){
            if(!$gender){
                $gender = [
                    Gender::MALE    => $group->where('gender', Gender::MALE)->count(),
                    Gender::FEMALE  => $group->where('gender', Gender::FEMALE)->count(),
                ];
            }
            else {
                if(abs($group->where('gender', Gender::MALE)->count() - $gender[Gender::MALE]) > $config['gender_diff'])
                    return false;
                if(abs($group->where('gender', Gender::FEMALE)->count() - $gender[Gender::FEMALE]) > $config['gender_diff'])
                    return false;
            }

            if(!$level){
                $level = [
                    Level::A => $group->where('level', Level::A)->count(),
                    Level::B => $group->where('level', Level::B)->count(),
                    Level::C => $group->where('level', Level::C)->count(),
                    Level::D => $group->where('level', Level::D)->count(),
                    Level::E => $group->where('level', Level::E)->count(),
                ];
            }
            else {

                if(!is_null($config['a_level_diff']) &&
                    abs($group->where('level', Level::A)->count() - $level[Level::A]) > $config['a_level_diff'])
                    return false;
                if(!is_null($config['b_level_diff']) &&
                    abs($group->where('level', Level::B)->count() - $level[Level::B]) > $config['b_level_diff'])
                    return false;
                if(!is_null($config['c_level_diff']) &&
                    abs($group->where('level', Level::C)->count() - $level[Level::C]) > $config['c_level_diff'])
                    return false;
                if(!is_null($config['d_level_diff']) &&
                    abs($group->where('level', Level::D)->count() - $level[Level::D]) > $config['d_level_diff'])
                    return false;
                if(!is_null($config['e_level_diff']) &&
                    abs($group->where('level', Level::E)->count() - $level[Level::E]) > $config['e_level_diff'])
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
            case 'A':
                return Level::A;
            case 'B':
                return Level::B;
            case 'C':
                return Level::C;
            case 'D':
                return Level::D;
            case 'E':
                return Level::E;
        }
    }

    protected function getConfig()
    {
        $str = request()->get('config');
        $configs = collect();
        foreach(explode("\n", $str) as $line){
            $config = explode('=', $line);
            $key = isset($config[0])? trim($config[0]): null;
            $value = isset($config[1])? (trim($config[1])!=''? trim($config[1]): null) : null;

            if($key){
                $configs[$key] = is_numeric($value)? (int)$value : $value;
            }
        };

        return $configs;
    }

}

class Gender {
    const MALE      = 'M';
    const FEMALE    = 'F';
}

class Level {
    const A     = 'A';
    const B     = 'B';
    const C     = 'C';
    const D     = 'D';
    const E     = 'E';
}

