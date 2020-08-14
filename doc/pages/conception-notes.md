Light Events, conception notes
==================
2019-10-31 -> 2020-08-14




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
2020-06-25 -> 2020-08-14


We believe in logs.

You can use the two service configuration options:

- debugSent:    bool=false, to log the names of the sent events
- debugCaught:  bool=false, to log information about the callable actually processing the events


We use the [Light_Logger](https://github.com/lingtalfi/Light_Logger) service under the hood, with the channel: **events.debug**.


