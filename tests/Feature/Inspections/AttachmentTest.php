<?php

declare(strict_types=1);

use App\Models\Inspection;
use App\Models\InspectionAttachment;
use App\Models\ServiceArea;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->contractor = User::factory()->contractor()->create();
    $this->inspection = Inspection::factory()->forContractor($this->contractor)->create();
});

test('photos upload to the private disk with their EXIF location', function () {
    $this->actingAs($this->contractor)
        ->post(route('inspections.attachments.store', $this->inspection), [
            'type' => 'photo',
            'file' => UploadedFile::fake()->image('db-front.jpg', 1600, 1200),
            'exif_lat' => 10.52217,
            'exif_lng' => 7.43831,
            'taken_at' => '2026-10-01T10:41:00+01:00',
        ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('type', 'photo')
        ->assertJsonPath('exifLat', 10.52217);

    $attachment = InspectionAttachment::query()->firstOrFail();

    expect($attachment->path)->toStartWith("inspections/{$this->inspection->uuid}/photo-")
        ->and($attachment->mime)->toBe('image/jpeg');
    Storage::disk('local')->assertExists($attachment->path);
});

test('layouts may be PDFs but photos must be images', function () {
    $pdf = UploadedFile::fake()->create('layout.pdf', 800, 'application/pdf');

    $this->actingAs($this->contractor)
        ->post(route('inspections.attachments.store', $this->inspection), ['type' => 'layout', 'file' => $pdf], ['Accept' => 'application/json'])
        ->assertCreated();

    $this->post(route('inspections.attachments.store', $this->inspection), [
        'type' => 'photo',
        'file' => UploadedFile::fake()->create('photo.pdf', 100, 'application/pdf'),
    ], ['Accept' => 'application/json'])->assertJsonValidationErrors('file');

    $this->post(route('inspections.attachments.store', $this->inspection), [
        'type' => 'layout',
        'file' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
    ], ['Accept' => 'application/json'])->assertJsonValidationErrors('file');
});

test('files over 10 MB are rejected', function () {
    $this->actingAs($this->contractor)
        ->post(route('inspections.attachments.store', $this->inspection), [
            'type' => 'layout',
            'file' => UploadedFile::fake()->create('huge.pdf', 10_241, 'application/pdf'),
        ], ['Accept' => 'application/json'])
        ->assertJsonValidationErrors('file');
});

test('removing an attachment deletes the file', function () {
    $this->actingAs($this->contractor)->post(route('inspections.attachments.store', $this->inspection), [
        'type' => 'photo',
        'file' => UploadedFile::fake()->image('a.jpg'),
    ], ['Accept' => 'application/json']);

    $attachment = InspectionAttachment::query()->firstOrFail();

    $this->deleteJson(route('inspections.attachments.destroy', [$this->inspection, $attachment]))->assertNoContent();

    Storage::disk('local')->assertMissing($attachment->path);
    $this->assertModelMissing($attachment);
});

test('submitted inspections take no new attachments', function () {
    $submitted = Inspection::factory()->forContractor($this->contractor)->submitted()->create();

    $this->actingAs($this->contractor)->post(route('inspections.attachments.store', $submitted), [
        'type' => 'photo',
        'file' => UploadedFile::fake()->image('late.jpg'),
    ], ['Accept' => 'application/json'])->assertForbidden();
});

test('attachments are served only to people allowed to see the inspection', function () {
    $kawo = ServiceArea::factory()->create(['name' => 'Kawo']);
    $doka = ServiceArea::factory()->create(['name' => 'Doka']);
    $inspection = Inspection::factory()->forContractor($this->contractor)->submitted()->inArea($kawo)->create();
    $attachment = $inspection->attachments()->first();
    Storage::disk('local')->put($attachment->path, 'jpeg-bytes');

    $this->actingAs($this->contractor)->get(route('attachments.show', $attachment))->assertOk();
    $this->actingAs(User::factory()->admin()->create())->get(route('attachments.show', $attachment))->assertOk();
    $this->actingAs(User::factory()->rep([$kawo])->create())->get(route('attachments.show', $attachment))->assertOk();

    // A rep for another area, and another contractor, are refused.
    $this->actingAs(User::factory()->rep([$doka])->create())->get(route('attachments.show', $attachment))->assertForbidden();
    $this->actingAs(User::factory()->contractor()->create())->get(route('attachments.show', $attachment))->assertForbidden();
});
