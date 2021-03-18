Light Events, conception notes
==================
2019-10-31 -> 2021-03-18




Why (skip this blabla)?
----------
2019-10-31


I thought for a long time before deciding to not implement an orm system.
One cool thing with having tables as objects though is the ability to add hooks into your objects, so that for instance
when an user is created (i.e. a row is inserted in the user table), your application can do something about it.

Well, I figured I would do this manually (which might be even more flexible), hence I need the light events system.





What
---------
2019-10-31


As you can probably guess from the name, this is just an event dispatching system.

Nothing special.

I also implemented a priority system, and a stop propagation system too.



Logs
-----------
2020-06-25 -> 2020-11-06


We believe in logs.

You can use the following service configuration options:

- debugDispatch: bool=false, whether to log when we dispatch an event.
- debugCall:  bool=false, whether to log when we trigger a listener.
- formattingDispatch: string=null, the [bashhtml](https://github.com/lingtalfi/CliTools/blob/master/doc/pages/bashtml.md) formatting to wrap the debugDispatch messages with. 
- formattingCall: string=null, the bashhtml formatting to wrap the debugCall messages with. 


Examples of formatting: 

- white
- white:bgRed



We use the [Light_Logger](https://github.com/lingtalfi/Light_Logger) service under the hood, with the channel: **events.debug**.





Events registration
---------
2021-03-18


Not all events are equal.

Some of them will be called on every page refresh, while some others will be called only a certain situations.

That's why we provide a hybrid registration system, composed of two systems:

- [open registration system](#open-events-registration-system)
- [close registration system](https://github.com/lingtalfi/Light/blob/master/personal/mydoc/pages/design/open-vs-close-service-registration.md#the-close-registration)


So, as a third-party plugin author, you can choose which one suits you best.
For instance if the event you listen to is not called often, you can use the open registration system.

On the other hand if you want to listen to an event that you know will be called all the time, just use the traditional closed registration system.


The open registered listeners are executed **AFTER** the one registered statically.








Open events registration system
------------
2020-08-14 -> 2021-03-18






We provide an [open registration system](https://github.com/lingtalfi/Light/blob/master/personal/mydoc/pages/design/open-vs-close-service-registration.md#the-open-registration), which works as explained below.




### The event file location
2021-03-18

Basically, for a given **$event_name** event, we will trigger all listeners defined in the following directory:

- **config/open/Ling.Light_Events/events/$event_name/**


To define a listener, create a [babyYaml](https://github.com/lingtalfi/BabyYaml) file which name is your [planetDotName](https://github.com/karayabin/universe-snapshot#the-planet-dot-name) 
(actually any babyYaml file will be parsed, but we recommend that you create only one babyYaml file per planet, to avoid [eco-structure](https://github.com/lingtalfi/Light/blob/master/personal/mydoc/pages/nomenclature.md#eco-structure) anarchy). 


So for instance if your plugin is MyGalaxy.PlanetOne, then create the following file:

- **config/open/Ling.Light_Events/events/$event_name/MyGalaxy.PlanetOne.byml**


For now, we only parse direct children of the event directory (i.e. sub-directories are not allowed).


By convention, the event name should start with the [planet dot name](https://github.com/karayabin/universe-snapshot#the-planet-dot-name) of the plugin issuing it.


So a concrete path of an **event file** would be this for instance:

- **config/open/Ling.Light_Events/Ling.Light_Database.on_lun_user_notification_create/MyGalaxy.PlanetOne.byml**



### The event file content
2021-03-18

As for the content of that babyYaml file, we expect an array of callables.

Each callable must be written in a special format, which you can think of as the [light execute notation](https://github.com/lingtalfi/Light/blob/master/personal/mydoc/pages/notation/light-execute-notation.md).


So for instance a file could contain something like this:

```yaml
- @some_service->method(ee,ff)
- @some_service2->method(ee,ff)
```


There are some extra variables available to you:

- data, mixed: the event data passed to the event (often in the form of a Light_Event instance)
- event, string: the name of the event being fired
- dynamicPath, string: the path to the file containing the registration nuggets
 
 
To access them from your method call, by convention your argument must have exactly the same name as the variable you want to use.
Note that those variables names are thus reserved by our service.


So for instance if you write this:

```yaml
- @some_service->method(ee,ff, data, event)
```

Then our service will call the **some_service->method** method with the following arguments:


- ee
- ff
- data
- event


The stopPropagation flag is also available, but not as a variable.
Instead, by default, the propagation doesn't stop, and your method can stop the propagation by returning 
the special string **LightEventsService::STOP_PROPAGATION**.














 




