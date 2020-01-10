<?php

namespace App\Http\Controllers\Admin;

use App\Models\Academy;
use App\Models\Coach;
use App\Models\Team;
use App\Models\TeamTime;
use App\Models\User;
use App\Traits\Dashboard\PublicTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use DB;
use Auth;
use Namshi\JOSE\Signer\SecLib\RS384;
use Validator;
use Hash;

class CoachController extends Controller
{
    use PublicTrait;

    public function index()
    {
        $coaches = Coach::selection()->get();
        return view('admin.coaches.index', compact('coaches'));
    }

    public function create()
    {
        $academies = Academy::active()->select('id', 'name_ar as name')->get();
        $teams = Team::active()->selection()->get();
        return view('admin.coaches.create', compact('academies', 'teams'));
    }


    public function store(Request $request)
    {

        $messages = [
            'name_ar.required' => 'أسم   المدرب  بالعربي  مطلوب  .',
            'name_en.required' => 'أسم  المدرب  بالانجليزي  مطلوب  .',
            'mobile.required' => 'رقم الهاتف مطلوب ',
            'mobile.unique' => 'رقم الهاتف مسجل لدينا من قبل ',
            'gender.required' => 'النوع مطلوب ',
            'gender.in' => ' ألنوع مطلوب  ',
            'academy_id.required' => 'لابد من احتيار الاكاديمية اولا ',
            'academy_id.exists' => 'هذه الاكاديمية غير موجوده ',
            'photo.required' => 'لابد من رفع صوره  المدرب  ',
            'photo.mimes' => ' أمتداد الصوره غير مسموح به ',
            "password.required" => trans("admin/passwords.passwordRequired"),
            "password.confirmed" => trans("admin/passwords.confirmpassword"),
            "password.min" => trans("admin/passwords.confirmpassword"),
            "teams.required" => 'لأابد من أختيار الفرق ',
            "teams.array" => 'لابد من أختيار الفرق ',
            "teams.min" => 'لابد من أختيار فرقه علي الاقل ',
            "teams.*.required" => ' لابد من أختيار  الفرق  ',
            "teams.*.exists" => "بعض الفرق المحتاره غير موجوده لدينا "

        ];

        $validator = Validator::make($request->all(), [
            'name_ar' => 'required|max:100',
            'name_en' => 'required|max:100',
            'mobile' => 'required|unique:coahes,mobile',
            'gender' => 'required|in:1,2',
            'academy_id' => 'required|exists:academies,id',
            'photo' => 'required|mimes:jpg,jpeg,png',
            'password' => 'required|confirmed|min:6',
            'teams' => 'required|array|min:1',
            'teams.*' => 'required|exists:teams,id'

        ], $messages);

        if ($validator->fails()) {
            notify()->error('هناك خطا برجاء المحاوله مجددا ');
            return redirect()->back()->withErrors($validator)->withInput($request->all());
        }

        DB::beginTransaction();
        try {
            $fileName = "";
            if (isset($request->photo) && !empty($request->photo)) {
                $fileName = $this->uploadImage('coaches', $request->photo);
            }
            $status = $request->has('status') ? 1 : 0;
            $coach = Coach::create(['photo' => $fileName, 'status' => $status] + $request->except('_token'));
            if ($coach->id) {
                $coach->teams()->attach($request->teams);
            }
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
        }
        notify()->success('تم اضافه المدرب بنجاح من فضلك قم بتحديد الفرق الخاصه بالمدرب');
        return redirect()->route('admin.coaches.all')->with(['success' => 'تم اضافه المدرب بنجاح من فضلك قم بتحديد الفرق الخاصه بالمدرب ']);
    }

    public function edit($id)
    {
        $data = [];
        $data['coach'] = Coach::findOrFail($id);
        $data['academies'] = Academy::active()->select('id', 'name_ar as name')->get();
        $data['teams'] = Team::active()->selection()->get();
        return view('admin.coaches.edit', $data);
    }

    public function update($id, Request $request)
    {

        $coach = Coach::findOrFail($id);
        $messages = [
            'name_ar.required' => 'أسم   المدرب  بالعربي  مطلوب  .',
            'name_en.required' => 'أسم  المدرب  بالانجليزي  مطلوب  .',
            'mobile.required' => 'رقم الهاتف مطلوب ',
            'mobile.unique' => 'رقم الهاتف مسجل لدينا من قبل ',
            'gender.required' => 'النوع مطلوب ',
            'gender.in' => ' ألنوع مطلوب  ',
            'academy_id.required' => 'لابد من احتيار الاكاديمية اولا ',
            'academy_id.exists' => 'هذه الاكاديمية غير موجوده ',
            'photo.required' => 'لابد من رفع صوره  المدرب  ',
            'photo.mimes' => ' أمتداد الصوره غير مسموح به ',
            "password.required" => trans("admin/passwords.passwordRequired"),
            "password.confirmed" => trans("admin/passwords.confirmpassword"),
            "password.min" => trans("admin/passwords.confirmpassword"),
            "teams.required" => 'لأابد من أختيار الفرق ',
            "teams.array" => 'لابد من أختيار الفرق ',
            "teams.min" => 'لابد من أختيار فرقه علي الاقل ',
            "teams.*.required" => ' لابد من أختيار  الفرق  ',
            "teams.*.exists" => "بعض الفرق المحتاره غير موجوده لدينا "

        ];
        $rules = [
            'name_ar' => 'required|max:100',
            'name_en' => 'required|max:100',
            'mobile' => 'required|unique:coahes,mobile,' . $coach->id . ',id',
            'gender' => 'required|in:1,2',
            'academy_id' => 'required|exists:academies,id',
            'photo' => 'mimes:jpg,jpeg,png',
            'teams' => 'required|array|min:1',
            'teams.*' => 'required|exists:teams,id'

        ];

        if ($request->filled('password')) {
            $rules['password'] = 'required|confirmed|min:6';
        }
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            notify()->error('هناك خطا برجاء المحاوله مجددا ');
            return redirect()->back()->withErrors($validator)->withInput($request->all());
        }

        try {
            $status = $request->has('status') ? 1 : 0;
            $request->request->add(['status' => $status]); //add request

            DB::beginTransaction();
            if (isset($request->photo) && !empty($request->photo)) {
                $fileName = $this->uploadImage('teams', $request->photo);
                $coach->update(['photo' => $fileName]);
            }
            $coach->update($request->except(['photo', 'password']));
            $coach->teams()->sync($request->teams);
            if ($request->filled('password')) {
            $coach -> update(['password' => $request -> password]);
            }
            DB::commit();
            notify()->success('تمت التعديل  بنجاح ');
            return redirect()->route('admin.coaches.all');
        } catch (\Exception $ex) {
            DB::rollback();
            return abort('404');
        }
    }

    public function getWorkingDay($teamId)
    {
        $team = Team::find($teamId);
        if (!$team) {
            notify()->error('الفريق  غير موجوده لدينا ');
            return redirect()->route('admin.teams.all');
        }

        $time = TeamTime::where('team_id', $teamId)->first();
        return view('admin.teams.workingdays', compact('academies', 'team', 'time'));
    }

    public function saveWorkingDay(Request $request)
    {
        try {
            $messages = [
                'team_id.required' => 'رقم الفرقه مطلوب .',
                'team_id.exists' => ' الفرقه غير موجوده .',
            ];

            $validator = Validator::make($request->all(), [
                'team_id' => 'required|exists:teams,id',
            ], $messages);

            if ($validator->fails()) {
                notify()->error('هناك خطا برجاء المحاوله مجددا ');
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }

            $inputs = ['id', 'saturday_status', 'sunday_status', 'monday_status', 'tuesday_status', 'wednesday_status', 'thursday_status', 'friday_status'];

            $team = Team::find($request->team_id);
            if (!$team) {
                notify()->error('الفريق  غير موجوده لدينا ');
                return redirect()->route('admin.teams.all');
            }
            $times = TeamTime::query();
            $times = $times->where('team_id', $request->team_id);
            if ($times->first()) {
                $teamTime = TeamTime::where('team_id', $request->team_id)->first();   // must load model first then update to let mutators work on update
                $teamTime->update($request->except($inputs + ['_token']));
                $this->setDayStatus($request);
                notify()->success('تم تحديث الاوقات بنجاح ');
                return redirect()->back();
            } else {

                $time = TeamTime::create($request->except($inputs));
                $request->request->team_id = $time;
                $this->setDayStatus($request);
                notify()->success('تم تحديث الاوقات بنجاح ');
                return redirect()->back();
            }
        } catch (\Exception $ex) {
            return abort('404');
        }
    }


    protected function setDayStatus(Request $request)
    {
        $status = ['saturday_status', 'sunday_status', 'monday_status', 'tuesday_status', 'wednesday_status', 'thursday_status', 'friday_status'];
        $times = TeamTime::where('team_id', $request->team_id)->first();
        foreach ($status as $st) {
            if ($request->has($st)) {
                $times->update([$st => 1]);
            } else {
                $times->update([$st => 0]);
            }
        }
    }


    public function teams($coachId)
    {
        $coach = Coach::findOrFail($coachId);
        $teams = $coach->teams()->get();
        notify()->success('تم عرض الفرق بنجاح  ');
        return view('admin.coaches.teams', compact('teams', 'coach'));
    }

    public function getCoachStudents($coachId)
    {
        $coach = Coach::findOrFail($coachId);
        $users =  User::whereHas('team',function ($q) use($coachId){
             $q -> whereHas('coaches',function ($qq) use($coachId){
                    $qq -> where('coach_id',$coachId);
             });
         }) -> get();
        return view('admin.coaches.users', compact('users', 'coach'));

    }
}
