<div id="esp-front-end">
    <div>
        <label for="superPassSelection">Pass:</label>
        <select id="superPassSelection">
            <option v-for="superPass in customer_data.super_passes">{{ superPass.name }}</option>
        </select>
    </div>
    <table>
        <thead>
            <tr>
                <th>Time Slot</th>
                <th>Event</th>
            </tr>
        </thead>
        <tbody>

        </tbody>
    </table>
</div>