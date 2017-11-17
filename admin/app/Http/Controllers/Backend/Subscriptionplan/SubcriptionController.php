<?php

namespace App\Http\Controllers\Backend\Subscriptionplan;

use App\Http\Controllers\Controller;
use App\Repositories\Backend\Subscription\SubscriptionRepository;
use App\Http\Requests\Backend\Subscriptions\SubscriptionRequest;
//use Session;
//use Illuminate\Support\Facades\Auth;

class SubcriptionController extends Controller
{

    /**
     * @var SubscriptionRepository
     */
    protected $subscriptionRepo;

    public function __construct(SubscriptionRepository $subscriptionRepo)
    {
        $this->subscriptionRepo = $subscriptionRepo;
    }

    /**
     * Function for add subscription UI
     * 
     * @return NULL
     */
    public function addSubcriptionUI($id = NULL)
    {
        if (isset($id)) {   
            $subscriptionDetail = $this->subscriptionRepo->getDetails($id);
            return view('backend.subscription.editsubs', ['subscriptionDetail' => $subscriptionDetail]);
        } else {
            $subscriptionDetail = $this->subscriptionRepo->getNewObject();
            return view('backend.subscription.addsubs', ['subscriptionDetail' => $subscriptionDetail]);
        }
    }

    /**
     * Function for list subscription UI
     * 
     * @param integer $id id of subscription of user send details
     * @return NULL
     */
    public function listSubcriptionUI($id = NULL)
    {
        if(isset($id)) {
            $subscriptionLists = $this->subscriptionRepo->getDetailsBehalfCategory($id);
            return view('backend.subscription.listsubscriptions', ['subscriptionLists' => $subscriptionLists]);
        }
        $subscriptionLists = $this->subscriptionRepo->listing();
        return view('backend.subscription.listsubscriptions', ['subscriptionLists' => $subscriptionLists]);
    }

    /**
     * Function for register subscription
     * 
     * @param object $request data of subscription details
     * @return NULL
     */
    public function registerSubcription(SubscriptionRequest $request)
    {

        $this->subscriptionRepo->register($request);

        $request->session()->flash('Status', 'Subscription saved successfully!');
        return redirect()->route('admin.subscriptionplan.list.subs');
    }
    
    /**
     * Function for register subscription
     * 
     * @param object $request data of subscription details.
     * @return NULL
     */
    public function editSubcription(SubscriptionRequest $request)
    {
        
        $this->subscriptionRepo->edit($request);

        $request->session()->flash('Status', 'Subscription update successfully!');
        return redirect()->route('admin.subscriptionplan.list.subs');
    }
    
    /**
     * Function for delete subscription
     * 
     * @param integer $request id of subscription
     * @return NULL
     */
    public function deleteSubcription($request)
    {
        
        $this->subscriptionRepo->delete($request);
        return redirect()->route('admin.subscriptionplan.list.subs');
    }
    
    /**
     * Function for add subscription API for 
     * 
     * @param integer $request id of subscription
     * @return NULL
     */
    public function addSubcriptionAPI($request)
    {
        return $this->subscriptionRepo->registerAPI($request);
    }

}
