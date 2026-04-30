<?php

use App\Livewire\Departments\Index;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(fn () => actingAs(User::factory()->create()));

it('lists departments with employee counts', function () {
    $eng = Department::factory()->create(['name' => 'Engineering']);
    Employee::factory()->count(2)->create(['department_id' => $eng->id]);

    Livewire::test(Index::class)
        ->assertSee('Engineering')
        ->assertSeeText('2');
});

it('paginates 10 per page', function () {
    Department::factory()->count(12)->create();

    Livewire::test(Index::class)
        ->assertSet('paginators.page', 1)
        ->call('gotoPage', 2)
        ->assertSet('paginators.page', 2);
});

it('opens the modal in create mode with empty fields', function () {
    Livewire::test(Index::class)
        ->assertSet('showForm', false)
        ->call('openCreate')
        ->assertSet('showForm', true)
        ->assertSet('editingId', null)
        ->assertSet('name', '');
});

it('opens the modal in edit mode with the row hydrated', function () {
    $dept = Department::factory()->create(['name' => 'Finance']);

    Livewire::test(Index::class)
        ->call('openEdit', $dept->id)
        ->assertSet('showForm', true)
        ->assertSet('editingId', $dept->id)
        ->assertSet('name', 'Finance');
});

it('validates name is required', function () {
    Livewire::test(Index::class)
        ->call('openCreate')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

it('validates name is unique on create', function () {
    Department::factory()->create(['name' => 'Sales']);

    Livewire::test(Index::class)
        ->call('openCreate')
        ->set('name', 'Sales')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

it('creates a department', function () {
    Livewire::test(Index::class)
        ->call('openCreate')
        ->set('name', 'Marketing')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    expect(Department::where('name', 'Marketing')->exists())->toBeTrue();
});

it('updates a department', function () {
    $dept = Department::factory()->create(['name' => 'Old Name']);

    Livewire::test(Index::class)
        ->call('openEdit', $dept->id)
        ->set('name', 'New Name')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    expect($dept->fresh()->name)->toBe('New Name');
});

it('allows updating without changing name (unique rule ignores self)', function () {
    $dept = Department::factory()->create(['name' => 'Same']);

    Livewire::test(Index::class)
        ->call('openEdit', $dept->id)
        ->set('name', 'Same')
        ->call('save')
        ->assertHasNoErrors();
});

it('blocks deletion when employees exist', function () {
    $dept = Department::factory()->create();
    Employee::factory()->create(['department_id' => $dept->id]);

    Livewire::test(Index::class)
        ->call('delete', $dept->id);

    expect(Department::find($dept->id))->not->toBeNull();
});

it('deletes a department with no employees', function () {
    $dept = Department::factory()->create();

    Livewire::test(Index::class)
        ->call('delete', $dept->id);

    expect(Department::find($dept->id))->toBeNull();
});
