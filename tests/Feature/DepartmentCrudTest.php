<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('lists departments', function () {
    Department::factory()->count(3)->create();

    $this->get(route('departments.index'))
        ->assertOk()
        ->assertSeeText('Departments');
});

it('creates a department', function () {
    $this->post(route('departments.store'), ['name' => 'Engineering'])
        ->assertRedirect(route('departments.index'));

    expect(Department::where('name', 'Engineering')->exists())->toBeTrue();
});

it('rejects duplicate department names', function () {
    Department::factory()->create(['name' => 'Engineering']);

    $this->post(route('departments.store'), ['name' => 'Engineering'])
        ->assertSessionHasErrors('name');
});

it('updates a department', function () {
    $department = Department::factory()->create(['name' => 'Old']);

    $this->put(route('departments.update', $department), ['name' => 'New'])
        ->assertRedirect(route('departments.index'));

    expect($department->fresh()->name)->toBe('New');
});

it('deletes an empty department', function () {
    $department = Department::factory()->create();

    $this->delete(route('departments.destroy', $department))
        ->assertRedirect(route('departments.index'));

    expect(Department::find($department->id))->toBeNull();
});

it('blocks deletion of a department with employees', function () {
    $department = Department::factory()->create();
    Employee::factory()->create(['department_id' => $department->id]);

    $this->delete(route('departments.destroy', $department))
        ->assertRedirect(route('departments.index'))
        ->assertSessionHas('error');

    expect(Department::find($department->id))->not->toBeNull();
});
