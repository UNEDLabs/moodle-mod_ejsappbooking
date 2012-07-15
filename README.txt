EJSAppBookings 1.0

This is the first release of the ejsappbooking plugin.
This plugin lets you add a booking system to handle the conecctions to the remote laboratories developed using EJS and added to your Moodle course using the ejsapp plugin.

This is a module plugin for Moodle so you should place the ejsappbooking folder in your /mod folder, inside you Moodle installation.

This module needs the ejsapp module to be of any use.
You can find and download at the plugins section in the Moodle.org webpage.

An explanation of EJSApp is included in the folder "doc". You will also find a txt file with relevant links.

#####################################

CONFIGURING THE EJAPPBOOKINGS PLUGIN:

The applets folder contains the following:

BookingClient folder, with the booking client applet, embedded by EJSAppBookings when this resource is added to a Moodle course.
BookingServer folder, with the booking server applet. This applet must be running on your Moodle server. Use it to configure your remote laboratories.

Both folders contain a "configuracion" folder for adding/editing languages presented in the bookings applets GUIs.
The "bookings_uned" folder has the sql data files you will need to install in your sql server. They are used by the BookingServer applet.

######################################
                                                      
EJSAppBooking is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

EJSAppBooking is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

The GNU General Public License is available on <http://www.gnu.org/licenses/>

EJSAppBooking has been developed by:
 - Javier Pavon: javi.pavon@gmail.com
 - Luis de la Torre: ldelatorre@dia.uned.es
 - Ruben Heradio: rheradio@issi.uned.es

  at the Computer Science and Automatic Control Department, Spanish Open University (UNED), 
  Madrid, Spain.