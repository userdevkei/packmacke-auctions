<form method="POST" action="{{ route('admin.releaseExternalTransfer', base64_encode($transfer->delivery_number.':'.$transfer->lot)) }}">
    @csrf
    <div class="row row-cols-2 mb-3">
        <div class="mb-2">
            <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">DESTINATION</label>
            <select class="form-select js-choice" name="warehouse_id" required>
                @foreach($warehouses as $warehouse)
                    <option
                        @selected($transfer && $warehouse->warehouse_id == $transfer->warehouse_id)
                        value="{{ $warehouse->warehouse_id }}">
                        {{ $warehouse->warehouse_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-2">
            <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">TRANSPORTER</label>
            <select name="transporter" id="transporterSelect2" class="form-select js-choice">
                <option selected disabled value="">-- select transporter --</option>
                @foreach($transporters as $transporter)
                    <option 
                        @selected($transfer && $transfer->transporter_id == $transporter->transporter_id)
                        value="{{ $transporter->transporter_id }}">
                        {{ $transporter->transporter_name }}
                    </option>
                @endforeach
                <option value="other">Other</option>
            </select>
            <input type="text" 
                name="transporter_other" 
                id="otherTransporterInput2" 
                class="form-control mt-2 d-none" 
                placeholder="Enter other transporter name" 
                style="height: 34% !important;">
        </div>

        <div class="mb-2">
            <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">DRIVER'S ID NUMBER</label> <br>
            <input id="idSelect" type="text" list="idList" name="idNumber" class="form-control idSelect" placeholder="-- Driver's ID Number --" required style="height: 67% !important;" value="{{ $transfer->id_number }}">
            <datalist id="idList">
                @foreach($users as $user)
                    <option value="{{ $user->id_number }}">{{ $user->id_number }}</option>
                @endforeach
            </datalist>
        </div>
        <div class="mb-2">
            <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">VEHICLE REGISTRATION</label><br>
            <input class="form-control" name="registration" type="text" placeholder="-- plate number --" required value="{{ $transfer->registration }}" style="height: 67%;">
        </div>
        <div class="mb-2">
            <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">DRIVER'S NAME</label>
            <input type="text" name="driverName" id="driverName" class="form-control driverName" value="{{ $transfer->driver_name }}" required style="height: 67% !important;">
        </div>

        <div class="mb-2">
            <label class="my-1 fs-xs fw-bold" style="font-size: 85% !important;">DRIVER'S PHONE NUMBER</label>
            <input type="text" name="driverPhone" id="driverPhone" class="form-control driverPhone" value="{{ $transfer->phone }}" required style="height: 67% !important;">
        </div>
    </div>
    <div class="d-flex justify-content-center">
        <button type="submit" class="btn btn-danger col-7" onclick="return confirm('Are you sure you want to proceed?')">UPDATE & RELEASE</button>
    </div>
</form>
