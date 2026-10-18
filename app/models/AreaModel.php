<?php
// app/models/AreaModel.php
// MODEL = the data. Areas of Mymensingh (shown in the register page dropdown).
// Later you can replace the list with a database query.

class AreaModel
{
    public static function getAll()
    {
        return [
            'Ahammadabad',
            'Akua',
            'Alia Madrasa Road',
            'Amlapara',
            'Baghmara',
            'BAU Area',
            'Biddamoye School Area',
            'Boro Bazar',
            'Boundary Road',
            'Bramopolli',
            'Bypass More',
            'Cantonment',
            'Chamra Godam',
            'Choto Bazar',
            'Dhopakhola',
            'Durgabari',
            'Fulpur',
            'Ganginapar',
            'Joynul Abedin Park',
            'Jubilee Road',
            'Kachari',
            'Kachi Jhuli Mor',
            'Kali Bari',
            'Kestopur',
            'Kewatkhali',
            'Lichu Bagan',
            'Maskanda',
            'Mintu College Area',
            'Nahar Road',
            'Natok Gol Lane',
            'Natun Bazar',
            'Naumahal',
            'Pat Godam',
            'Pocha Pukur Par',
            'Police Line',
            'Pondit Para',
            'Shehora',
            'Shema Choron Rai Road',
            'Shomvogonj',
            'Stadium Area',
            'Station Road',
            'Taltola',
            'Town Hall Mor',
            'Trishal Bus Stand',
            'Zilla More',
        ];
    }
}
