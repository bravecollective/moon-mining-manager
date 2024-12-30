@extends('layouts.master')

@section('title', 'Edit Renter')

@section('content')

    @if ($errors->any())
        <div class="row">
            <div class="col-6">
                <div class="card-heading">Errors!</div>
                <div class="card errors">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <form method="post" action="/renters/{{ $renter->id }}">

        {{ csrf_field() }}

        <div class="row">

            <div class="col-6">

                <div class="card-heading">Renter details</div>

                <div class="card information">
                    <div>
                        <label for="type">Type of rental</label>
                        <select id="type" name="type">
                            <option value="{{\App\Models\Renter::TYPE_ACTIVE}}"
                                    {{ ($renter->type == \App\Models\Renter::TYPE_ACTIVE) ? ' selected' : '' }}>
                                Active
                            </option>
                            <!-- <option value="{{\App\Models\Renter::TYPE_PASSIVE}}"
                                    {{ ($renter->type == \App\Models\Renter::TYPE_PASSIVE) ? ' selected' : '' }}>
                                Passive
                            </option> -->
                        </select>
                    </div>
                    <div>
                        <label for="character">Character</label>
                        <div class="character-search" style="display: flex;">
                            <input type="text" id="character" value="{{ $character->name }}"
                                   placeholder="Start typing to search by character name...">
                            <button class="search">Find character</button>
                        </div>
                        <small>
                            If the search returns the wrong character you can also search by character ID,
                            which can be found on <a href="https://evewho.com/" target="_blank">EveWho</a>
                            for example.
                        </small>
                        <input type="hidden" id="character_id" name="character_id" value="{{ $renter->character_id }}">
                        <div class="search-response"></div>
                        <div class="character-card">
                            <img src="{{ $character->portrait }}" alt="">
                            <div class="character-name">{{ $character->name }}</div>
                            <div class="character-corporation">{{ $character->corporation }}</div>
                        </div>
                    </div>
                    <div>
                        <label for="refinery_id">Refinery to be rented</label>
                        <select id="refinery_id" name="refinery_id">
                            <option value="">Select refinery:</option>
                            @foreach ($refineries as $refinery)
                                <option value="{{ $refinery->observer_id }}"
                                        {{ ($renter->refinery_id == $refinery->observer_id) ? ' selected' : '' }}>
                                    {{ $refinery->name }}
                                </option>
                            @endforeach
                        </select>
                        <span>(if not yet dropped, enter details below)</span>
                    </div>
                    <div>
                        <label for="moon_id">Location</label>
                        <select id="moon_id" name="moon_id">
                            <option value="">Select moon</option>
                            @foreach ($moons as $moon)
                                <option value="{{ $moon->id }}"{{ ($renter->moon_id == $moon->id) ? ' selected' : '' }}>
                                    {{ $moon->id }} - {{ $moon->getName() }}
                                </option>
                            @endforeach
                        </select>
                        <span>(Unavailable moons will show as "Select moon")</span>
                    </div>
                    <div>
                        <label for="monthly_rental_fee">Monthly rental fee</label>
                        <input type="text" id="monthly_rental_fee" name="monthly_rental_fee"
                               value="{{ $renter->monthly_rental_fee }}">
                    </div>
                    <div>
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes">{{ $renter->notes }}</textarea>
                    </div>
                    <div>
                        <label for="start_date">Contract start date (yyyy-mm-dd)</label>
                        <input type="text" id="start_date" name="start_date" value="{{ $renter->start_date }}">
                    </div>
                    <div>
                        <label for="end_date">Contract end date (yyyy-mm-dd)</label>
                        <input type="text" id="end_date" name="end_date"
                               value="{{ $renter->end_date }}" placeholder="{{ date('Y-m-d') }}">
                    </div>
                    <div class="form-actions">
                        <button type="submit">Save changes</button>
                    </div>
                </div>

            </div>

        </div>

    </form>

    <script>
    
        window.addEventListener('load', function () {
            doSearch();
            $('.search').on('click', function (e) {
                doSearch();
                e.preventDefault();
            });
        });

        function doSearch() {
            $.get('/search', {
                'q': $('#character').val()
            }, function (data) {
                if (typeof data == 'string') {
                    $('.character-card').fadeOut();
                    $('.search-response').fadeIn().text(data);
                } else {
                    $('.search-response, .search-options').fadeOut();
                    $('#character_id').val(data.id);
                    $('#character').val(data.name);
                    $('.character-card img').attr('src', data.portrait);
                    $('.character-name').text(data.name);
                    $('.character-corporation').text(data.corporation);
                    $('.character-card').fadeIn();
                }
            });
        }
    
    </script>

@endsection
