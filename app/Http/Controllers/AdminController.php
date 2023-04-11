<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class AdminController extends Controller
{

    //------------------------
    // Constructs
    //------------------------
    public function __construct(Request $request) {
        $this->middleware('axuadmin'); //only admin can enter here
    }

    //-----------------------
    // Default variables
    //-----------------------
    public $default_url_adminuser = "/adminuser";   //default return URL
    public $default_lpp = 25;                       //default line per page
    public $default_start_page = 1;                 //default starting page

    //------------------------
    // Admin Dashboard
    //------------------------
    public function index(Request $request) {
        $data = array();
        $data['userinfo'] = $userinfo = $request->get('userinfo');
        return view('admin.home', $data);
    }

    //------------------------
    // Admin User Main Page
    //------------------------
    public function adminuser(Request $request) {
        $data = array();
        $data['userinfo'] = $userinfo = $request->get('userinfo'); //print_r($userinfo);
        //Array ( [0] => 1 [1] => Dhon [2] => Collera [3] => Cunanan [4] => me@dhonc.com [5] => admin [6] => blank.jpg [7] => 230315183724 )

        //error lists
        $data['errorlist'] = [
            1 => 'All forms are required please try again.',
            2 => 'Your password is too short, it should be at least 8 characters long.',
            3 => 'Both password and retype password are not the same.',
            4 => 'the email already existed, please try to check the user on the list.',
            5 => 'This user doed not exist',
            6 => 'status should only be Active or Inactive',
        ];
        $data['error'] = 0;
        if (!empty($_GET['e'])) {
            $data['error'] = $_GET['e'];
        }

        //notification lists
        $data['notiflist'] = [
            1 => 'New User has been saved.',
            2 => 'Changes has been saved.',
            3 => 'Password has been changed.',
            4 => 'User has been deleted.'
        ];
        $data['notif'] = 0;
        if (!empty($_GET['n'])) {
            $data['notif'] = $_GET['n'];
        }

        $query = $request->query(); //print_r($query);
        $qstring = array(); //querystrings

        //lines per page section
        $lpp = $this->default_lpp;
        //urlperline
        $lineperpage = [ 3, 25, 50, 100, 200 ];
        if (!empty($query['lpp'])) {
            if (in_array($query['lpp'], $lineperpage)) {
                $lpp = $query['lpp'];
            }
        }
        $data['lpp'] = $qstring['lpp'] = $lpp;

        //keyword section
        $keyword = '';
        if (!empty($query['keyword'])) {
            $qstring['keyword'] = $keyword = $query['keyword'];
            $data['keyword'] = $keyword;
        }

        //orderby
        $data['sort'] = 0;
        $data['orderbylist'] = [
            ['display'=>'ID', 'field'=>'main_users.id' ],
            ['display'=>'Username/Email', 'field'=>'main_users.email'],
            ['display'=>'Last Name', 'field'=>'main_users_details.lastname' ],
            ['display'=>'First Name', 'field'=>'main_users_details.firstname' ],
            ['display'=>'Middle Name', 'field'=>'main_users_details.middlename' ],
        ];
        if (!empty($query['sort'])) {
            $data['sort'] = $qstring['sort'] = $query['sort'];
        }


        //paging section
        $page = $this->default_start_page;
        if (!empty($query['page'])) {
            $page = $query['page'];
        }
        $qstring['page'] = $page;
        $countdata = DB::table('main_users')->leftJoin('main_users_details', 'main_users_details.userid', '=', 'main_users.id')->where('accounttype', 'admin')->count();
        $dbdata = DB::table('main_users')->select('main_users.*', 'main_users_details.firstname', 'main_users_details.lastname', 'main_users_details.middlename', 'main_users_details.mobilenumber', 'main_users_details.address',)
            ->leftJoin('main_users_details', 'main_users_details.userid', '=', 'main_users.id')
            ->where('accounttype', 'admin');
        if (!empty($keyword)) {
            //dbcounting with keyword
            $countdata = DB::table('main_users')->leftJoin('main_users_details', 'main_users_details.userid', '=', 'main_users.id')->where('accounttype', 'admin')
                ->Where('main_users.email', 'like', "%$keyword%")
                ->orWhere('main_users_details.firstname', 'like', "%$keyword%")
                ->orWhere('main_users_details.lastname', 'like', "%$keyword%")
                ->orWhere('main_users_details.middlename', 'like', "%$keyword%")
                ->orWhere('main_users_details.mobilenumber', 'like', "%$keyword%")
                ->orWhere('main_users_details.address', 'like', "%$keyword%")
                ->count();
            /*
            $dbdata->Where(function(Builder $query) {
                $query->orWhere('main_users.email', $keyword)
                    ->orWhere('main_users_details.firstname', $keyword)
                    ->orWhere('main_users_details.lastname', $keyword)
                    ->orWhere('main_users_details.middlename', $keyword)
                    ->orWhere('main_users_details.mobilenumber', $keyword)
                    ->orWhere('main_users_details.address', $keyword);
            });
            */
            $dbdata->Where('main_users.email', 'like', "%$keyword%");
            $dbdata->orWhere('main_users_details.firstname', 'like', "%$keyword%");
            $dbdata->orWhere('main_users_details.lastname', 'like', "%$keyword%");
            $dbdata->orWhere('main_users_details.middlename', 'like', "%$keyword%");
            $dbdata->orWhere('main_users_details.mobilenumber', 'like', "%$keyword%");
            $dbdata->orWhere('main_users_details.address', 'like', "%$keyword%");

        }
        //orderby
        $dbdata->orderBy($data['orderbylist'][$data['sort']]['field']);

        //compute pages
        $data['totalpages'] = ceil($countdata / $lpp);
        $data['page'] = $page;
        $data['totalitems'] = $countdata;
        $dataoffset = ($page*$lpp)-$lpp; //echo $dataoffset;
        //add paging on data
        $dbdata->offset($dataoffset)->limit($lpp);
        $data['qstring'] = http_build_query($qstring);
        $data['qstring2'] = $qstring;

        //paging URL settings
        if ($page < 2) {
            //disabled URLS of first and previous button
            $data['page_first_url'] = '<a class="btn btn-success disabled" href="#" role="button" aria-disabled="true" style="padding-top: 10px;"><i class="fa-solid fa-angles-left"></i> </a>';
            $data['page_prev_url'] = '<a class="btn btn-success disabled" href="#" role="button" aria-disabled="true" style="padding-top: 10px;"><i class="fa-solid fa-angle-left"></i> </a>';
        } else {
            $urlvar = $qstring; $urlvar['page'] = 1; //firstpage
            $data['page_first_url'] = '<a class="btn btn-success" href="?'.http_build_query($urlvar).'" role="button" style="padding-top: 10px;"><i class="fa-solid fa-angles-left"></i> </a>';
            $urlvar = $qstring; $urlvar['page'] = $urlvar['page'] - 1; // current page minus 1 for prev
            $data['page_prev_url'] = '<a class="btn btn-success" href="?'.http_build_query($urlvar).'" role="button" style="padding-top: 10px;"><i class="fa-solid fa-angle-left"></i> </a>';
        }
        if ($page >= $data['totalpages']) {
            //disabled URLS on next and last button
            $data['page_last_url'] = '<a class="btn btn-success disabled" href="#" role="button" aria-disabled="true" style="padding-top: 10px;"><i class="fa-solid fa-angles-right"></i> </a>';
            $data['page_next_url'] = '<a class="btn btn-success disabled" href="#" role="button" aria-disabled="true" style="padding-top: 10px;"><i class="fa-solid fa-angle-right"></i> </a>';
        } else {
            $urlvar = $qstring; $urlvar['page'] = $data['totalpages']; //lastpage
            $data['page_last_url'] = '<a class="btn btn-success" href="?'.http_build_query($urlvar).'" role="button" style="padding-top: 10px;"><i class="fa-solid fa-angles-right"></i> </a>';
            $urlvar = $qstring; $urlvar['page'] = $urlvar['page'] + 1; //nest page
            $data['page_next_url'] = '<a class="btn btn-success" href="?'.http_build_query($urlvar).'" role="button" style="padding-top: 10px;"><i class="fa-solid fa-angle-right"></i> </a>';
        }


        //$tosql = $dbdata->toSql(); print_r($tosql); die();
        $data['dbresult'] = $dbresult = $dbdata->get()->toArray();
        //echo "<pre>"; print_r($dbresult); echo "</pre>";
        //print_r($countdata);

        //query builder

        //$tbluser = DB::table('main_users')->join('main_users_details', 'main_users_details.userid', '=', 'main_users.id');

        return view('admin.adminusers', $data);
    }
}