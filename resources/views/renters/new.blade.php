@extends('layouts.master')

@section('title', 'Add New Renter')

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

    <form method="post" action="/renters/new">

        {{ csrf_field() }}

        <div class="row">

            <div class="col-6">

                <div class="card-heading">New renter details</div>

                <div class="card information">
                    <div>
                        <label for="type">Type of rental</label>
                        <select id="type" name="type" onchange="updateFee()">
                            <option value="{{\App\Models\Renter::TYPE_ACTIVE}}">Active</option>
                            <option value="{{\App\Models\Renter::TYPE_PASSIVE}}">Passive</option>
                        </select>
                    </div>
                    <div>
                        <label for="character">Character</label>
                        <div class="character-search" style="display: flex;">
                            <input type="text" id="character" placeholder="Start typing to search by character name...">
                            <button class="search">Find character</button>
                        </div>
                        <small>
                            If the search returns the wrong character you can also search by character ID,
                            which can be found on <a href="https://evewho.com/" target="_blank">EveWho</a>
                            for example.
                        </small>
                        <input type="hidden" id="character_id" name="character_id">
                        <div class="search-response"></div>
                        <div class="character-card">
                            <img src="" alt="">
                            <div class="character-name"></div>
                            <div class="character-corporation"></div>
                        </div>
                    </div>
                    <div>
                        <label for="refinery_id">Refinery to be rented</label>
                        <select id="refinery_id" name="refinery_id">
                            <option value="">Select refinery:</option>
                            @foreach ($refineries as $refinery)
                                <option value="{{ $refinery->observer_id }}">{{ $refinery->name }}</option>
                            @endforeach
                        </select>
                        <span>(if not yet dropped, enter details below)</span>
                    </div>
                    <div>
                        <label for="moon_id">Location</label>
                        <select id="moon_id" name="moon_id" onchange="updateFee()">
                            <option value="">Select moon</option>
                            @foreach ($moons as $moon)
                                <option value="{{ $moon->id }}"
                                        data-fee="{{ $moon->monthly_rental_fee }}"
                                        data-corp-fee="{{ $moon->monthly_corp_rental_fee }}">
                                    {{ $moon->id }} - {{ $moon->getName() }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="monthly_rental_fee">Monthly rental fee</label>
                        <input type="text" id="monthly_rental_fee" name="monthly_rental_fee">
                    </div>
                    <div>
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes"></textarea>
                    </div>
                    <div>
                        <label for="start_date">Contract start date (yyyy-mm-dd)</label>
                        <input type="text" id="start_date" name="start_date" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="form-actions">
                        <button type="submit">Create rental contract</button>
                    </div>
                </div>

            </div>

        </div>

    </form>

    <script>
    
        window.addEventListener('load', function () {
            $('.search').on('click', function (e) {
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
                e.preventDefault();
            });
        });

        function updateFee() {
            if ($('#type').val() === 'individual') {
                var fee = $('#moon_id').find('option:selected').data('fee')
            } else {
                var fee = $('#moon_id').find('option:selected').data('corpFee')
            }
            $('#monthly_rental_fee').val(fee);
        }

    </script>

@endsection
