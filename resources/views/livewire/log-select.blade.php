<div>
    <button wire:click="getZip(2025,01)">Log </button>
    <div wire:ignore>
        <select
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" 
            wire:model="selected_file"
            wire:change="fileSelected">
            @foreach($file_list as $key=>$file)
                <option id="{{$key}}">{{basename($file)}}</option>
            @endforeach
        </select>
    </div>
</div>
