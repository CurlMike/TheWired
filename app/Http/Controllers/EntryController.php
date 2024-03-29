<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\User;
use App\Models\Entry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EntryController extends Controller
{
    public function homeIndex() {

        $entries = Entry::with('user')->orderBy('created_at', 'desc')->get();

        return view('home', ['entries' => $entries]);
    }

    public function createEntry(Request $request) {

        $user = Auth::user();

        $data = $request->validate([
            'title' => 'max:63',
            'content' => 'required|max:65535',
            'media' => 'file|mimes:jpeg,png,jpg,gif,svg|max:5126'
        ]);

        $entry = new Entry();
        $entry->user_id = $user->id;
        $entry->content = $data['content'];

        if(isset($data['title'])) {
            $entry->title = $data['title'];
        }

        if($request->hasFile('media')) {
            $image = $request->file('media');
            $imageName = time() . '.' . $image->extension();
            $image->move(public_path('storage/entries'), $imageName);
            $entry->media = $imageName;
        }

        $entry->save();

        return redirect()->route('home');
    }

    public function deleteEntry($id) {

        $entry = Entry::find($id);

        $this->authorize('deleteEntry', $entry);

        $entry->delete();

        return redirect()->route('home');
    }

    public function likeEntry(Request $request) {
        $entryId = $request->json()->get('entry_id');
        $entry = Entry::find($entryId);

        $this->authorize('likeEntry', $entry);

        $user = Auth::user();

        $like = new Like();
        $like->user_id = $user->id;
        $like->entry_id = $entry->id;

        $like->save();

        return response()->json([], 201);
    }

    public function dislikeEntry(Request $request) {
        $entryId = $request->json()->get('entry_id');
        $entry = Entry::find($entryId);

        $this->authorize('dislikeEntry', $entry);

        $user = Auth::user();

        $like = Like::where('user_id', $user->id)
        ->where('entry_id', $entry->id);

        $like->delete();

        return response()->json([], 201);
    }
}
