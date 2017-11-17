<?php
namespace App\Repositories\Backend\Subscription;

use App\Models\Admin\Subscription\Subscription;
use App\Repositories\BaseRepository;
/**
 * Class VideosRepository.
 * 
 * @category Videos
 * @package  Videos
 */
class SubscriptionRepository extends BaseRepository
{
    
    public function getNewObject()
    {
        return new Subscription;
    }
    
    /**
     * Function for list all subscription information
     *  
     * @return array $subslist subscription information
     */
    public function listing()
    {
        $subscription_obj = new Subscription();
        $subscription_list = $subscription_obj::all();
        return $subscription_list;
    }
    
    /**
     * Function for list all subscription information
     *  
     * @return array $subslist subscription information
     */
    public function getDetails($id)
    {
        $subscription_obj = new Subscription();
        $subscription_detail = $subscription_obj::where('id', $id)->first();
        return $subscription_detail;
    }
    
    
    
    /**
     * Function for register subscription details
     * 
     * @param type $request
     * @return type
     */
    public function register($request) {
        $data = $request->input();
        $subscription_obj = new Subscription();
        $subscription_insert = $subscription_obj->insert([
            'SKU' => isset($data['SKU']) ? $data['SKU'] : NULL,
            'tutorials' => isset($data['tutorials']) ? $data['tutorials'] : NULL,
            'tournaments' => isset($data['tournaments']) ? $data['tournaments'] : NULL,
            'battles' => isset($data['battles']) ? $data['battles'] : NULL,
            'tournament_details' => isset($data['tournament_details']) ? $data['tournament_details'] : NULL,
            'battle_details' => isset($data['battle_details']) ? $data['battle_details'] : NULL,
            'tutorial_details' => isset($data['tutorial_details']) ? $data['tutorial_details'] : NULL,
            'name' => isset($data['name']) ? $data['name'] : NULL,
            'duration' => isset($data['duration']) ? $data['duration'] : NULL,
            'price' => isset($data['price']) ? $data['price'] : NULL,
            'status' => isset($data['status']) ? $data['status'] : NULL,
        ]);
        return $subscription_insert;
    }
    
    /**
     * Function for register subscription details
     * 
     * @param type $request
     * @return type
     */
    public function edit($request) {
        $data = $request->input();
        $subscription_obj = new Subscription();
        $subscription_update = $subscription_obj->where('id', $data['id'])->update([
            'SKU' => isset($data['SKU']) ? $data['SKU'] : NULL,
            'tutorials' => isset($data['tutorials']) ? $data['tutorials'] : NULL,
            'tournaments' => isset($data['tournaments']) ? $data['tournaments'] : NULL,
            'battles' => isset($data['battles']) ? $data['battles'] : NULL,
            'tournament_details' => isset($data['tournament_details']) ? $data['tournament_details'] : NULL,
            'battle_details' => isset($data['battle_details']) ? $data['battle_details'] : NULL,
            'tutorial_details' => isset($data['tutorial_details']) ? $data['tutorial_details'] : NULL,
            'name' => isset($data['name']) ? $data['name'] : NULL,
            'status' => isset($data['status']) ? $data['status'] : NULL,
            'duration' => isset($data['duration']) ? $data['duration'] : NULL,
            'price' => isset($data['price']) ? $data['price'] : NULL,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return $subscription_update;
    }
    
    /**
     * Function for delete subscription
     * 
     * @param type $id
     * @return type
     */
    public function delete($id){
        $subscription = new Subscription;
        $subscription->where('id', $id)->delete();
        return true;
    }
    
    /**
     * Function for register API subscription details for android application
     * 
     * @param type $request
     * @return type object
    */
    public function registerAPI($request) {
        $data = $request->input();
        $subscription_obj = new Subscription();
        $subscription_insert = $subscription_obj->insert([
            'email_id' => isset($data['email_id']) ? $data['email_id'] : NULL,
            'subscription_id' => isset($data['subscription_id']) ? $data['subscription_id'] : NULL,
            'order_id' => isset($data['order_id']) ? $data['order_id'] : NULL,
            'purchase_token' => isset($data['purchase_token']) ? $data['purchase_token'] : NULL,
            'purchase_time' => isset($data['purchase_time']) ? $data['purchase_time'] : NULL,
            'is_auto_renewing' => isset($data['is_auto_renewing']) ? $data['is_auto_renewing'] : NULL,
        ]);
        return $subscription_insert;
    }    
}