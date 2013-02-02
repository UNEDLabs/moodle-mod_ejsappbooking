######################
# EJSAppBooking 1.3 #
######################

1. Content
==========

This plugin lets you add a booking system to handle the conections to the remote
laboratories developed using EJS and added to your Moodle course using the ejsapp plugin.

2. License
==========

EJSAppBooking is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

EJSAppBooking is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

The GNU General Public License is available on <http://www.gnu.org/licenses/>

3. Installation
===============

If you downloaded this plugin from github, you will need to change the folder's name to
ejsappbooking. If you downloaded it from Moodle.org, then you are fine.

This is a module plugin for Moodle so you should place the ejsappbooking folder in your /mod
folder, inside you Moodle installation.
This module has been tested in all Moodle 2.x versions.

 WARNING: If you are updating ejsappbooking from a previous version, DO NOT replace your old 
 applets/BookingServer/configuracion folder inside your old ejsappbooking directory with the one 
 in the newer version. Otherwise, you will need to reconfigure the parameters set in point 6.

4. Dependencies
===============

This module needs the ejsapp module to be of any use. It works with version 1.4 (or later) of 
EJSApp. You can find and download it at https://moodle.org/plugins/view.php?plugin=mod_ejsappbooking,
in the plugins section in the Moodle.org webpage or at https://github.com/UNEDLabs.

An explanation of EJSApp is included in the folder "doc". There, you will also find a txt 
file with relevant links.

IMPORTANT: This module requires the use of a MySQL databse.

5. Client Configuration
=======================

The applets folder contains the following:

BookingClient folder, with the booking client applet, embedded by EJSAppBooking when this 
resource is added into a Moodle course.
BookingServer folder, with the booking server applet. This application must be running on  
your Moodle server. You can also use it to configure the remote laboratories you have 
previously added to your Moodle site using the EJSApp plugin. Finally, it also offers a nice
way to configure your mail system to use with Moodle.

Both folders contain a folder called "configuracion" with files for adding/editing languages 
presented in the bookings applets GUIs.

6. Server Configuration
=======================

First, you need to configure the server so it can connect to your sql database. You can do that
in two different ways:

First one (using the GUI):

1. Go to the applets/BookingServer/configuracion folder.

2. Edit the valores.dat file and change interfaz=0 to interfaz=1. This makes the booking server
application to run showing a graphical user interface.

3. Run the BookingServer.jar applet.

4. Click on the options tab of the top menu.

5. Select the "Server settings" option.

6. Enter the needed data to let the booking server access your sql database.

7. Use the "Test" button to check whether the booking server can access the database or not.

Second one (editing text files):

1. Go to the applets/BookingServer/configuracion folder.

2. Edit the moodle.dat file and modify the user=root password= lines with the data of your sql
admin user.

3. You can then run the BookingServer.jar applet without the GUI.

IMPORTANT! Either way, you always need to execute the BookingServer.jar file with administrator
privileges.

If the GUI is activated, once the previous test has been passed, you can either check and/or 
modify the configuration of both the remote experimental systems added to your Moodle site by 
means of the EJSApp plugin and the mail server configuration. This can be done selecting the 
"Plants management" and "Mail server settings" options in the top menu of the application.

7. Authors
==========

EJSAppBooking has been developed by:
 - Luis de la Torre: ldelatorre@dia.uned.es
 - Ruben Heradio: rheradio@issi.uned.es
 - Javier Pavon: javi.pavon@gmail.com

  at the Computer Science and Automatic Control Department, Spanish Open University (UNED), 
  Madrid, Spain.