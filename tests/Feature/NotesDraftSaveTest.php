<?php

use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('clicking new note enters draft mode without saving empty note to database', function () {
    $user = User::factory()->create();

    // Initially 0 notes
    expect(Note::where('user_id', $user->id)->count())->toBe(0);

    $test = Livewire::actingAs($user)
        ->test('notes')
        ->call('createNote');

    // Verify still 0 notes in DB!
    expect(Note::where('user_id', $user->id)->count())->toBe(0);
    expect($test->get('isCreating'))->toBeTrue();
    expect($test->get('selectedNoteId'))->toBeNull();

    // Trigger saveNote without typing anything
    $test->call('saveNote');

    // Still 0 notes in DB!
    expect(Note::where('user_id', $user->id)->count())->toBe(0);
});

test('typing title or content creates a new note in database', function () {
    $user = User::factory()->create();

    $test = Livewire::actingAs($user)
        ->test('notes')
        ->call('createNote')
        ->set('title', 'Catatan Proyek Penting')
        ->set('content', 'Ini adalah isi catatan rapat.')
        ->call('saveNote');

    // Now exactly 1 note should exist in DB!
    expect(Note::where('user_id', $user->id)->count())->toBe(1);

    $note = Note::where('user_id', $user->id)->first();
    expect($note->title)->toBe('Catatan Proyek Penting');
    expect($note->content)->toBe('Ini adalah isi catatan rapat.');

    // Component should have selected this new note
    expect($test->get('selectedNoteId'))->toBe($note->id);
    expect($test->get('isCreating'))->toBeFalse();
});

test('switching note while in empty draft does not save empty note', function () {
    $user = User::factory()->create();
    $existingNote = Note::create([
        'user_id' => $user->id,
        'title' => 'Catatan Pertama',
        'content' => 'Konten pertama',
    ]);

    expect(Note::where('user_id', $user->id)->count())->toBe(1);

    $test = Livewire::actingAs($user)
        ->test('notes')
        ->call('createNote');

    // Draft mode active, still 1 note
    expect($test->get('isCreating'))->toBeTrue();
    expect(Note::where('user_id', $user->id)->count())->toBe(1);

    // Switch back to existing note without typing anything
    $test->call('selectNote', $existingNote->id);

    expect($test->get('isCreating'))->toBeFalse();
    expect($test->get('selectedNoteId'))->toBe($existingNote->id);

    // Verify still only 1 note in database!
    expect(Note::where('user_id', $user->id)->count())->toBe(1);
});

test('quick notes drawer createQuickNote does not save until content or title is provided', function () {
    $user = User::factory()->create();

    expect(Note::where('user_id', $user->id)->count())->toBe(0);

    $test = Livewire::actingAs($user)
        ->test('quick-notes-drawer')
        ->call('openDrawer')
        ->call('createQuickNote');

    // Must NOT insert empty row
    expect(Note::where('user_id', $user->id)->count())->toBe(0);
    expect($test->get('isCreating'))->toBeTrue();

    // Call saveNote without typing
    $test->call('saveNote');
    expect(Note::where('user_id', $user->id)->count())->toBe(0);

    // Close drawer without typing
    $test->call('closeDrawer');
    expect(Note::where('user_id', $user->id)->count())->toBe(0);

    // Reopen and type content
    $test->call('openDrawer')
        ->call('createQuickNote')
        ->set('content', 'Ide cepat di scratchpad')
        ->call('saveNote');

    // Now 1 note exists in database!
    expect(Note::where('user_id', $user->id)->count())->toBe(1);
    $created = Note::where('user_id', $user->id)->first();
    expect($created->content)->toBe('Ide cepat di scratchpad');
    expect($test->get('selectedNoteId'))->toBe($created->id);
});

test('creating tes1 and tes2 then switching preserves both note titles without duplication', function () {
    $user = User::factory()->create();

    $test = Livewire::actingAs($user)
        ->test('notes')
        ->call('createNote')
        ->set('title', 'tes1')
        ->set('content', 'isi tes 1')
        ->call('saveNote');

    $note1 = Note::where('user_id', $user->id)->where('title', 'tes1')->first();
    expect($note1)->not->toBeNull();

    // Create second note
    $test->call('createNote')
        ->set('title', 'tes2')
        ->set('content', 'isi tes 2')
        ->call('saveNote');

    $note2 = Note::where('user_id', $user->id)->where('title', 'tes2')->first();
    expect($note2)->not->toBeNull();
    expect($note1->id)->not->toBe($note2->id);

    // Switch back to note 1
    $test->call('selectNote', $note1->id);

    // Both note 1 and note 2 must preserve their original titles in the database!
    expect($note1->fresh()->title)->toBe('tes1');
    expect($note1->fresh()->content)->toBe('isi tes 1');

    expect($note2->fresh()->title)->toBe('tes2');
    expect($note2->fresh()->content)->toBe('isi tes 2');

    // Component state must reflect note 1
    expect($test->get('selectedNoteId'))->toBe($note1->id);
    expect($test->get('title'))->toBe('tes1');
    expect($test->get('content'))->toBe('isi tes 1');

    // Switch back to note 2
    $test->call('selectNote', $note2->id);

    expect($note1->fresh()->title)->toBe('tes1');
    expect($note2->fresh()->title)->toBe('tes2');
    expect($test->get('selectedNoteId'))->toBe($note2->id);
    expect($test->get('title'))->toBe('tes2');
    expect($test->get('content'))->toBe('isi tes 2');
});

test('action button in each note card allows archiving and deleting individual notes directly', function () {
    $user = User::factory()->create();

    $note1 = Note::create([
        'user_id' => $user->id,
        'title' => 'Catatan 1',
        'content' => 'Konten 1',
        'is_archived' => false,
    ]);

    $note2 = Note::create([
        'user_id' => $user->id,
        'title' => 'Catatan 2',
        'content' => 'Konten 2',
        'is_archived' => false,
    ]);

    $test = Livewire::actingAs($user)
        ->test('notes')
        ->assertSeeHtml('confirmDeleteNote(' . $note1->id . ')')
        ->assertSeeHtml('toggleArchive(' . $note1->id . ')')
        ->assertSeeHtml('confirmDeleteNote(' . $note2->id . ')')
        ->assertSeeHtml('toggleArchive(' . $note2->id . ')');

    // Archive note 2 directly via card action
    $test->call('toggleArchive', $note2->id);
    expect($note2->fresh()->is_archived)->toBeTrue();
    expect($note1->fresh()->is_archived)->toBeFalse();

    // Confirm delete note 1 directly via card action
    $test->call('confirmDeleteNote', $note1->id)
        ->assertDispatched('open-modal', name: 'delete-note-modal');

    expect($test->get('noteToDeleteId'))->toBe($note1->id);

    // Call deleteNote
    $test->call('deleteNote')
        ->assertDispatched('close-modal', name: 'delete-note-modal');

    expect(Note::find($note1->id))->toBeNull();
    expect(Note::find($note2->id))->not->toBeNull();
});

test('notes list card displays absolute date time format instead of relative time', function () {
    $user = User::factory()->create();
    $note = Note::create([
        'user_id' => $user->id,
        'title' => 'Catatan Timestamp',
        'content' => 'Konten Timestamp',
        'updated_at' => now(),
    ]);

    $expectedDateTime = $note->updated_at->format('d M Y, H:i');

    Livewire::actingAs($user)
        ->test('notes')
        ->assertSeeHtml($expectedDateTime);
});

test('note editor textarea uses font-sans matching preview font instead of font-mono', function () {
    $user = User::factory()->create();
    $note = Note::create([
        'user_id' => $user->id,
        'title' => 'Test Font Note',
        'content' => 'Konten font',
    ]);

    Livewire::actingAs($user)
        ->test('notes')
        ->call('selectNote', $note->id)
        ->assertSeeHtml('font-sans leading-relaxed custom-scrollbar');
});

test('scratchpad changes synchronize with full notes page and preserve content', function () {
    $user = User::factory()->create();
    $note = Note::create([
        'user_id' => $user->id,
        'title' => 'Catatan Sinkronisasi',
        'content' => 'Konten awal',
    ]);

    // Open notes page first
    $notesPage = Livewire::actingAs($user)
        ->test('notes')
        ->call('selectNote', $note->id);

    expect($notesPage->get('content'))->toBe('Konten awal');

    // Open scratchpad and edit the note
    $drawer = Livewire::actingAs($user)
        ->test('quick-notes-drawer')
        ->call('openDrawer', $note->id)
        ->set('content', 'Konten diperbarui dari scratchpad!')
        ->call('closeDrawer')
        ->assertDispatched('note-updated', id: $note->id);

    // Database is updated
    expect($note->fresh()->content)->toBe('Konten diperbarui dari scratchpad!');

    // Dispatch note-updated event to notes page
    $notesPage->dispatch('note-updated', id: $note->id);

    // Notes page now reflects updated content without stale overwrite!
    expect($notesPage->get('content'))->toBe('Konten diperbarui dari scratchpad!');

    // Reopen scratchpad drawer, content is preserved!
    $drawerReopen = Livewire::actingAs($user)
        ->test('quick-notes-drawer')
        ->call('openDrawer', $note->id);

    expect($drawerReopen->get('content'))->toBe('Konten diperbarui dari scratchpad!');
});

test('openFull method in quick-notes-drawer saves note and redirects to full notes page', function () {
    $user = User::factory()->create();
    $note = Note::create([
        'user_id' => $user->id,
        'title' => 'Catatan Open Full',
        'content' => 'Teks awal',
    ]);

    Livewire::actingAs($user)
        ->test('quick-notes-drawer')
        ->call('openDrawer', $note->id)
        ->set('content', 'Teks sebelum open full')
        ->call('openFull')
        ->assertRedirect(route('notes', ['selected' => $note->id]));

    expect($note->fresh()->content)->toBe('Teks sebelum open full');
});
test('note auto-populates excerpt column on save and avoids content load on notes list', function () {
    $user = User::factory()->create();
    $note = Note::create([
        'user_id' => $user->id,
        'title' => 'Catatan Benchmark Excerpt',
        'content' => '<h1>Judul Heading</h1><p>Ini adalah paragraf pengujian untuk excerpt yang disimpan otomatis ke kolom database agar tidak perlu parse content.</p>',
    ]);

    expect($note->fresh()->excerpt)->not->toBeEmpty();
    expect($note->fresh()->excerpt)->toContain('Judul Heading');
    expect($note->fresh()->excerpt)->not->toContain('<h1>');

    // Test noteCounts computed property
    $test = Livewire::actingAs($user)->test('notes');
    $counts = $test->get('noteCounts');

    expect($counts)->toBeArray();
    expect($counts['all'])->toBe(1);
    expect($counts['pinned'])->toBe(0);
    expect($counts['archived'])->toBe(0);

    // Pin note and verify
    $note->update(['is_pinned' => true]);
    $countsAfterPin = Livewire::actingAs($user)->test('notes')->get('noteCounts');
    expect($countsAfterPin['pinned'])->toBe(1);

    // Archive note and verify
    $note->update(['is_archived' => true]);
    $countsAfterArchive = Livewire::actingAs($user)->test('notes')->get('noteCounts');
    expect($countsAfterArchive['all'])->toBe(0);
    expect($countsAfterArchive['archived'])->toBe(1);
});
