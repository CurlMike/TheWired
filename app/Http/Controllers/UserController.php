<?php

namespace App\Http\Controllers;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function index($account_name) {

        try {
            $user = User::where('account_name', $account_name)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            abort(404);
        }

        $entries = $user->entries()->orderBy('created_at', 'desc')->get();

        $likedEntries = $user->likedEntries()->orderBy('created_at', 'desc')->get();

        return view('users', ['user' => $user, 'entries' => $entries, 'likedEntries' => $likedEntries]);
    }

    public function editIndex($account_name) {
        
        try {
            $user = User::where('account_name', $account_name)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            abort(404);
        }

        $this->authorize('editProfile', $user);

        return view('edit_profile', ['user' => $user]);
    }

    public function updateUser(Request $request, $account_name) { 

        try {
            $user = User::where('account_name', $account_name)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            abort(404);
        }

        $this->authorize('editProfile', $user);

        $data = $request->validate([
            'username' => 'required|max:63',
            'email' => 'required|email|max:63',
            'bio' => 'max:255',
            'private' => 'boolean',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'banner' => 'nullable|image|mimes:jpeg,png,jpg|max:4096'
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                if (file_exists(public_path('storage/avatars/' . $user->avatar)) && $user->avatar != 'default_avatar.png') {
                    unlink(public_path('storage/avatars/' . $user->avatar));
                }
            }
            $avatarName = time() . '.' . request()->avatar->extension();
            request()->avatar->move(public_path('storage/avatars/'), $avatarName);
            $data['avatar'] = $avatarName;
        }

        if ($request->hasFile('banner')) {
            if ($user->banner) {
                if (file_exists(public_path('storage/banners/' . $user->banner)) && $user->banner != 'default_banner.png') {
                    unlink(public_path('storage/banners/' . $user->banner));
                }
            }
            $bannerName = time() . '.' . request()->banner->extension();
            request()->banner->move(public_path('storage/banners/'), $bannerName);
            $data['banner'] = $bannerName;
        }

        $user->update(array_merge($data, ['private' => request('private', 0)]));

        return redirect()->route('user.index', ['account_name' => $user->account_name]);
    }

    public function follow(Request $request, $account_name) {
        if ($json = $request->json()->all()) {
            $account_name = $json['account_name'];
        }

        try {
            $authUser = auth()->user();
            $user = User::where('account_name', $account_name)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            abort(404);
        }
        
        $this->authorize('followAccount', $user);

        Follow::create([
            'follower_id' => $authUser->id,
            'following_id' => $user->id,
            'status' => $user->private ? 'pending' : 'accepted'
        ]);

        if ($json) {
            return response()->json([], 201);
        }
        return redirect()->back();
    }

    public function unfollow(Request $request, $account_name) {
        if ($json = $request->json()->all()) {
            $account_name = $json['account_name'];
        }
        
        try {
            $authUser = auth()->user();
            $user = User::where('account_name', $account_name)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            abort(404);
        }
        
        $this->authorize('unfollowAccount', $user);

        $followRequest = Follow::where('follower_id', $authUser->id)
        ->where('following_id', $user->id);

        $followRequest->delete();

        if ($json) {
            return response()->json([], 201);
        }
        return redirect()->back();
    }

    public function settingsIndex($account_name) {
        try {
            $user = User::where('account_name', $account_name)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            abort(404);
        }

        $this->authorize("AccessSettings", $user);

        return view('user-settings', ['user' => $user]);
    }

    public function deleteAccount($account_name) {
        try {
            $user = User::where('account_name', $account_name)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            abort(404);
        }

        $this->authorize("DeleteAccount", $user);

        $user->delete();

        return redirect()->route('welcome');
    }
}
