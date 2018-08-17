<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\{Battles, Combos, ComboSets, Workouts, User};
use App\Helpers\{Push, PushTypes};

class BattleController extends Controller
{

    /**
     * @api POST /battles
     * 
     * Send battle invite
     * 
     * @param Request $request
     *
     * @return json
     */
    public function postBattleWithInvite(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'opponent_user_id' => 'required|integer',
            'plan_id' => 'required|integer',
            'type_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            
            $errors = $validator->errors();

            return response()->json(['error' => 'true', 'message' => $errors]);
        }

        $opponentUserId = $request->get('opponent_user_id');

        try {

            $battle = Battles::create([
                        'user_id' => \Auth::user()->id,
                        'opponent_user_id' => $opponentUserId,
                        'plan_id' => $request->get('plan_id'),
                        'type_id' => $request->get('type_id')
            ]);

            $opponentUser = $battle->opponentUser;

            // Send Push Notification
            $pushMessage = \Auth::user()->first_name . ' ' . \Auth::user()->last_name . ' has invited you for battle';

            // push send
            Push::send(PushTypes::BATTLE_INVITE, $opponentUserId, \Auth::user()->id, $pushMessage, ['battle_id' => $battle->id]);

            // Generates new notification for user
            \App\UserNotifications::generate(\App\UserNotifications::BATTLE_CHALLENGED, $opponentUserId, \Auth::user()->id, $battle->id);

            return response()->json([
                        'error' => 'false',
                        'message' => 'User invited for battle successfully',
                        'data' => ['battle_id' => $battle->id, 'time' => strtotime($battle->created_at)]
            ]);

        } catch (\Exception $exception) {

            return response()->json(['error' => 'true', 'message' => $exception->getMessage()]);
        }    
    }

    /**
     * @api GET  /battles/<battle_id> 
     * 
     * Get battle details
     * 
     * @param int $battleId
     *
     * @return json
     */
    public function getBattle($battleId)
    {
        $validator = \Validator::make(['battle_id' => $battleId], [
            'battle_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            
            $errors = $validator->errors();

            return response()->json(['error' => 'true', 'message' => $errors]);
        }

        $_battle = Battles::find($battleId);
        
        if ($_battle) {

            try {

                // Opponent user is opponent of current logged in user
                $opponentUserId = ($_battle->user_id == \Auth::user()->id) ? $_battle->opponent_user_id : $_battle->user_id;

                $opponentUser = User::get($opponentUserId);

                $battle = $_battle->toArray();

                $battle['opponent_user'] = $opponentUser->toArray();

                // ID of user who created the battle
                $battle['sender_user_id'] = $_battle->user_id;

                $battle['shared'] = filter_var($_battle->user_shared, FILTER_VALIDATE_BOOLEAN);

                if (\Auth::user()->id == $_battle->opponent_user_id) {
                    $battle['shared'] = filter_var($_battle->opponent_shared, FILTER_VALIDATE_BOOLEAN);
                }

                // Battle result
                $battle['battle_result'] = Battles::getResult($battleId);

                switch ($_battle->type_id) {
                    case \App\Types::COMBO:
                        $plan = \App\Combos::get($_battle->plan_id);
                        break;
                    case \App\Types::COMBO_SET:
                        $plan = \App\ComboSets::get($_battle->plan_id);
                        break;
                    case \App\Types::WORKOUT:
                        $plan = \App\Workouts::get($_battle->plan_id);
                        break;
                    default:
                        $plan = null;
                }

                if ($plan) {
                    $planDetail = [
                        'id' => $plan['id'],
                        'name' => $plan['name'],
                        'description' => $plan['description'],
                        'detail' => $plan['detail']
                    ];

                    $battle['plan_detail'] = ['type_id' => (int) $_battle->type_id, 'data' => json_encode($planDetail)];
                }

                return response()->json(['error' => 'false', 'message' => '', 'data' => $battle]);
            
            } catch (\Exception $exception) {

                return response()->json(['error' => 'true', 'message' => $exception->getMessage()]);
            }

        } else {

            return response()->json(['error' => 'true', 'message' => 'Battle not found']);
        }

    }

    /**
     * @api GET  /battles/resend/<battle_id> 
     * 
     * Resend battle invite
     * 
     * @param int $battleId
     *
     * @return json
     */
    public function resendBattleInvite($battleId)
    {

        $validator = \Validator::make(['battle_id' => $battleId], [
            'battle_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            
            $errors = $validator->errors();

            return response()->json(['error' => 'true', 'message' => $errors]);
        }

        $battle = Battles::find($battleId);

        if ($battle) {

            try { 
                
                $user = $battle->user;
                $opponentUser = $battle->opponentUser;

                // Send Push Notification
                $pushMessage = $user->first_name . ' ' . $user->last_name . ' has invited you for battle';

                Push::send(PushTypes::BATTLE_RESEND, $battle->opponent_user_id, \Auth::user()->id, $pushMessage, ['battle_id' => $battle->id]);

                return response()->json([
                    'error' => 'false',
                    'message' => 'User invited for battle successfully',
                    'data' => ['battle_id' => $battle->id, 'time' => strtotime($battle->created_at)]
                ]);

            } catch (\Exception $exception) {

                return response()->json(['error' => 'true', 'message' => $exception->getMessage()]);
            }

        } else {

            return response()->json(['error' => 'true', 'message' => 'Battle not found']);
        }
    }
    
    /**
     * @api POST  /battles/accept_decline 
     * 
     * Accept or Decline battle invite
     * 
     * @param Request $request
     *
     * @return json
     */
    public function updateBattleInvite(Request $request)
    {
        
        $validator = \Validator::make($request->all(), [
            'battle_id' => 'required|integer',
            'accept' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            
            $errors = $validator->errors();

            return response()->json(['error' => 'true', 'message' => $errors]);
        }


        $battleId = $request->get('battle_id');


        $accepted = filter_var($request->get('accept'), FILTER_VALIDATE_BOOLEAN);

        try {

            $battle = Battles::find($battleId);

            if ($battle) {

                $user = $battle->user;
                $opponentUser = $battle->opponentUser;

                // Send push notification to sender user (who created battle)
                $pushMessage = $opponentUser->first_name . ' ' . $opponentUser->last_name . ' has ' . ($accepted ? 'accepted' : 'declined') . ' battle';

                // $pushOpponentUser = User::get($battle->opponent_user_id);

                $pushType = ($accepted) ? PushTypes::BATTLE_ACCEPT : PushTypes::BATTLE_DECLINE;

                // Push::send($battle->user_id, $pushType, $pushMessage, $pushOpponentUser);
                Push::send($pushType, $battle->user_id, $battle->opponent_user_id, $pushMessage, ['battle_id' => $battle->id]);

                if ($accepted === false) {
                    $battle->delete();
                } else {
                    $battle->accepted = $accepted;
                    $battle->accepted_at = date('Y-m-d H:i:s');
                    $battle->save();
                }

                return response()->json([
                    'error' => 'false',
                    'message' => 'User ' . ($accepted ? 'accepted' : 'declined') . ' battle',
                    'data' => ['battle_id' => $battle->id, 'time' => strtotime($battle->created_at)]
                ]);
            }

        } catch (\Exception $exception) {

            return response()->json(['error' => 'true', 'message' => $exception->getMessage()]);
        }    
    }

    /**
     * @api GET  /battles/cancel/<battle_id> 
     * 
     * Cancel battle
     * 
     * @param $battleId int
     *
     * @return json
     */
    public function cancelBattle($battleId)
    {
        $validator = \Validator::make(['battle_id' => $battleId], [
            'battle_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            
            $errors = $validator->errors();

            return response()->json(['error' => 'true', 'message' => $errors]);
        }

        try {

            $battle = Battles::find($battleId);

            if ($battle && $battle->user_id == \Auth::user()->id)
                $battle->delete();

            $user = $battle->user;

            $opponentUser = $battle->opponentUser;

            // Send Push Notification to opponent-user of battle
            $pushMessage = $user->first_name . ' ' . $user->last_name . ' has cancelled battle';

            Push::send(PushTypes::BATTLE_CANCEL, $battle->opponent_user_id, \Auth::user()->id, $pushMessage, ['battle_id' => $battle->id]);

            return response()->json([
                        'error' => 'false',
                        'message' => 'Battle cancelled successfully',
                        'data' => ['battle_id' => $battle->id, 'time' => strtotime($battle->created_at)]
            ]);

        } catch (\Exception $exception) {

            return response()->json(['error' => 'true', 'message' => $exception->getMessage()]);
        }     
    }

    /**
     * @api GET /battles/combos 
     * 
     * Get list of available combos
     *
     * @return json
     */
    public function getCombos()
    {   
        try {

            $combos = Combos::select('*', \DB::raw('id as key_set'), \DB::raw('id as tags'))->get()->toArray();

            foreach ($combos as $i => $combo) {
                $keySet = $combo['key_set'];

                $combos[$i]['keys'] = explode('-', $keySet);
                $combos[$i]['videos'] = \App\Videos::where('type_id', \App\Types::COMBO)->where('plan_id', $combo['id'])->first();
            }

            return response()->json(['error' => 'false', 'message' => '', 'data' => $combos]);

        } catch (\Exception $exception) {

            return response()->json(['error' => 'true', 'message' => $exception->getMessage()]);
        }    
    }

    /**
     * @api GET /battles/combo_sets 
     * 
     * Get list of combo-sets
     * 
     *
     * @return json
     */    
    public function getComboSets()
    {
        $comboSets = [];

        try {
            
            $_comboSets = ComboSets::select('*', \DB::raw('id as tags'))->get();

            foreach ($_comboSets as $comboSet) {
                $_comboSet = $comboSet->toArray();
                $_comboSet['combos'] = $comboSet->combos->pluck('combo_id')->toArray();

                $comboSets[] = $_comboSet;
            }

            return response()->json(['error' => 'false', 'message' => '', 'data' => $comboSets]);

        } catch (\Exception $exception) {

            return response()->json(['error' => 'true', 'message' => $exception->getMessage()]);
        }
    }

    /**
     * @api GET /battles/workouts 
     * 
     * Get list of workouts
     * 
     *
     * @return json
     */
    public function getWorkouts()
    {

        $workouts = [];

        try {

            $_workouts = Workouts::select('*', \DB::raw('round_time as round_time'), \DB::raw('rest_time as rest_time'), \DB::raw('prepare_time as prepare_time'), \DB::raw('warning_time as warning_time'), \DB::raw('id as tags'))->get();

            foreach ($_workouts as $workout) {
                $_workout = $workout->toArray();
                $combos = [];

                foreach ($workout->rounds as $round) {
                    $combos[] = $round->combos->pluck('combo_id')->toArray();
                }

                $_workout['combos'] = $combos;

                $workouts[] = $_workout;
            }

            return response()->json(['error' => 'false', 'message' => '', 'data' => $workouts]);

        } catch (\Exception $exception) {

            return response()->json(['error' => 'true', 'message' => $exception->getMessage()]);
        }    
    }

    /**
     * @api GET /battles/received 
     * 
     * Get list of received battles
     * 
     *
     * @return json
     */
    public function getReceivedRequests(Request $request)
    {
        $offset = (int) ($request->get('start') ? $request->get('start') : 0);
        $limit = (int) ($request->get('limit') ? $request->get('limit') : 20);

        $userId = \Auth::user()->id;

        try {

            $battle_requests = Battles::select('battles.id as battle_id', 'user_id as opponent_user_id', 'first_name', 'last_name', 'photo_url', 'battles.created_at as time')
                            ->join('users', 'users.id', '=', 'battles.user_id')
                            ->where('opponent_user_id', $userId)
                            ->where(function ($query) {
                                $query->whereNull('accepted')->orWhere('accepted', 0);
                            })
                            ->orderBy('battles.updated_at', 'desc')
                            ->offset($offset)->limit($limit)->get()->toArray();
            $data = [];
            $i = 0;
            foreach ($battle_requests as $battle_request) {
                $data[$i]['battle_id'] = $battle_request['battle_id'];
                $data[$i]['time'] = strtotime($battle_request['time']);
                $data[$i]['opponent_user'] = User::get($battle_request['opponent_user_id']);
                $i++;
            }

            return response()->json(['error' => 'false', 'message' => '', 'data' => $data]);

        } catch (\Exception $exception) {

            return response()->json(['error' => 'true', 'message' => $exception->getMessage()]);
        }     
    }

    /**
     * @api GET /battles/my_battles 
     * 
     * Get list of sent request battles
     * 
     * @param Request $request
     *
     * @return json
     */
    public function getMyBattles(Request $request)
    {
        $offset = (int) ($request->get('start') ? $request->get('start') : 0);
        $limit = (int) ($request->get('limit') ? $request->get('limit') : 20);
        $userId = \Auth::user()->id;

        try {

            $requested_by_opponent = Battles::select('battles.id as battle_id', 'user_id', 'opponent_user_id', 'battles.created_at  as time')
                            ->where(function ($query) use($userId) {
                                $query->where('opponent_user_id', $userId)->where('accepted', TRUE)->where(function ($query1) use($userId) {
                                    $query1->where('user_finished', 0)->orWhereNull('user_finished')->orWhere('opponent_finished', 0)->orWhereNull('opponent_finished');
                                });
                            })
                            ->orWhere(function ($query) use($userId) {
                                $query->where('user_id', $userId)->where(function ($query1) use($userId) {
                                    $query1->where('user_finished', 0)->orWhereNull('user_finished')->orWhere('opponent_finished', 0)->orWhereNull('opponent_finished');
                                });
                            })
                            ->orderBy('battles.updated_at', 'desc')->offset($offset)->limit($limit)->get()->toArray();
            $data = [];
            $i = 0;
            foreach ($requested_by_opponent as $battle_request) {
                $data[$i]['battle_id'] = $battle_request['battle_id'];
                $data[$i]['time'] = strtotime($battle_request['time']);
                $battle_request['opponent_user_id'] = ($battle_request['opponent_user_id'] == $userId) ? $battle_request['user_id'] : $battle_request['opponent_user_id'];
                $data[$i]['opponent_user'] = User::get($battle_request['opponent_user_id']);
                $i++;
            }
            return response()->json(['error' => 'false', 'message' => '', 'data' => $data]);

        } catch (\Exception $exception) {

            return response()->json(['error' => 'true', 'message' => $exception->getMessage()]);
        }     
    }
 
    /**
     * @api GET /battles/finished 
     * 
     * Get list of finished battles 
     * 
     * @param Request $request
     *
     * @return json
     */
    public function getAllFinishedBattles(Request $request)
    {
        $offset = (int) ($request->get('start') ? $request->get('start') : 0);
        $limit = (int) ($request->get('limit') ? $request->get('limit') : 20);

        $days = (int) ($request->get('days') ? $request->get('days') : null);

        try {
            
            $userId = \Auth::user()->id;
            $data = Battles::getFinishedBattles($userId, $days, $offset, $limit);

            return response()->json(['error' => 'false', 'message' => '', 'data' => $data['finished']]);

        } catch (\Exception $exception) {

            return response()->json(['error' => 'true', 'message' => $exception->getMessage()]);
        }     
    }

    /**
     * @api GET /battles/all 
     * 
     * Get list of all battles 
     * 
     * @param Request $request
     *
     * @return json
     */
    public function getAllBattles(Request $request)
    {
        $useBattleData = array();
        $userId = \Auth::user()->id;

        try {

            $battle_requests = Battles::select('battles.id as battle_id', 'user_id as opponent_user_id', 'first_name', 'last_name', 'photo_url', 'battles.created_at as time')
                            ->join('users', 'users.id', '=', 'battles.user_id')
                            ->where('opponent_user_id', $userId)
                            ->where(function ($query) {
                                $query->whereNull('accepted')->orWhere('accepted', 0);
                            })
                            ->orderBy('battles.updated_at', 'desc')->get()->toArray();
            $data = [];
            $i = 0;
            foreach ($battle_requests as $battle_request) {
                $data[$i]['battle_id'] = $battle_request['battle_id'];
                $data[$i]['time'] = strtotime($battle_request['time']);
                $data[$i]['opponent_user'] = User::get($battle_request['opponent_user_id']);
                $i++;
            }
            $useBattleData['received'] = $data;

            $requested_by_opponent = Battles::select('battles.id as battle_id', 'user_id', 'opponent_user_id', 'battles.created_at  as time')
                            ->where(function ($query) use($userId) {
                                $query->where('opponent_user_id', $userId)->where('accepted', TRUE)->where(function ($query1) use($userId) {
                                    $query1->where('user_finished', 0)->orWhereNull('user_finished')->orWhere('opponent_finished', 0)->orWhereNull('opponent_finished');
                                });
                            })
                            ->orWhere(function ($query) use($userId) {
                                $query->where('user_id', $userId)->where(function ($query1) use($userId) {
                                    $query1->where('user_finished', 0)->orWhereNull('user_finished')->orWhere('opponent_finished', 0)->orWhereNull('opponent_finished');
                                });
                            })
                            ->orderBy('battles.updated_at', 'desc')->get()->toArray();
            $my_battle_data = [];
            $j = 0;
            foreach ($requested_by_opponent as $battle_request) {
                $my_battle_data[$j]['battle_id'] = $battle_request['battle_id'];
                $my_battle_data[$j]['time'] = strtotime($battle_request['time']);
                $battle_request['opponent_user_id'] = ($battle_request['opponent_user_id'] == $userId) ? $battle_request['user_id'] : $battle_request['opponent_user_id'];
                $my_battle_data[$j]['opponent_user'] = User::get($battle_request['opponent_user_id']);
                $j++;
            }
            $useBattleData['my_battles'] = $my_battle_data;
            $finished = Battles::getFinishedBattles($userId);
            $useBattleData['finished'] = $finished['finished'];

            return response()->json(['error' => 'false', 'message' => '', 'data' => $useBattleData]);

        } catch (\Exception $exception) {

            return response()->json(['error' => 'true', 'message' => $exception->getMessage()]);
        }   
    }

    /**
     * @api GET /combos/audio  
     * 
     * Set audio in combos
     * 
     * @param Request $request
     *
     * @return json
     */
    public function saveAudio(Request $request)
    {
        try {

            $userId = \Auth::user()->id;
            $comboId = $request->combo_id;
            $combo = Combos::findOrFail($comboId);
            $image = $combo->audio;
            $file = $request->file('audio_file');
            if ($image != "") {
                $url = url() . '/storage';
                $pathToFile = str_replace($url, storage_path(), $image);
                if (file_exists($pathToFile)) {
                    unlink($pathToFile); //delete earlier audio
                }
            }
            $dest = 'storage/comboAudio';
            if ($request->hasFile('audio_file')) {
                $imgOrgName = $file->getClientOriginalName();
                $nameWithoutExt = pathinfo($imgOrgName, PATHINFO_FILENAME);
                $ext = pathinfo($imgOrgName, PATHINFO_EXTENSION);
                $imgOrgName = $nameWithoutExt . '-' . time() . '.' . $ext;  //make audio name unique
                $file->move($dest, $imgOrgName);
                $gif_path = url() . '/' . $dest . '/' . $imgOrgName; // path to be inserted in table
                $combo->audio = $gif_path;
                $combo->user_id = $userId;
                $combo->save();
            }
            return response()->json(['error' => 'false', 'message' => 'Audio uploaded successfully!', 'data' => $combo]);
        } catch (\Exception $exception) {

            return response()->json(['error' => 'true', 'message' => $exception->getMessage()]);
        }     
    }

    /**
     * @api GET /battles/combos/audio 
     * 
     * Get list of available combos with audio
     * 
     *
     * @return json
     */
    public function getCombosAudio()
    {
        $combos = Combos::select('id', 'name', 'audio')->get()->toArray();

        return response()->json(['error' => 'false', 'message' => '', 'data' => $combos]);
    }

    /**
     * @api GET /battles/user/finished 
     * 
     * Get list of finished battles by user
     * 
     * @param Request $request
     *
     * @return json
     */
    public function getUsersFinishedBattles(Request $request)
    {
        $offset = (int) ($request->get('start') ? $request->get('start') : 0);
        $limit = (int) ($request->get('limit') ? $request->get('limit') : 20);

        $userId = $request->get('user_id');

        $data = Battles::getFinishedBattles($userId, null, $offset, $limit);

        return response()->json(['error' => 'false', 'message' => '', 'data' => $data['finished']]);
    }
}
