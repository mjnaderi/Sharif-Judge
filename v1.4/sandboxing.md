# Sandbox

Sharif Judge runs lots of user-submitted arbitrary codes. It should run codes in a restricted environment. So we need tools to sandbox submitted codes in Sharif Judge.

You can improve security by enabling [shield](shield.md) alongside Sandbox.


## C/C++ Sabdboxing

Sharif Judge uses [EasySandbox](https://github.com/daveho/EasySandbox) for sandboxing C/C++ codes. EasySandbox limits the running code using **[seccomp](http://lwn.net/Articles/332974/)**, a sandboxing mechanism in Linux kernel.

By default, EasySandbox is disabled in Sharif Judge. You can enable it from `Settings` page. But you must "build EasySandbox" before enabling it.

### Build EasySandbox

EasySandbox files are located in `tester/easysandbox`. For building EasySandbox run:

```bash
$ cd tester/easysandbox
$ chmod +x runalltests.sh
$ chmod +x runtest.sh
$ make runtests
```

If it printed the message `All tests passed!`, EasySandbox is built successfully and can be enabled on your system. You can enable EasySandbox in `Settings` page.


## Java Sandboxing

Java sandbox is enabled by default using Java Security Manager. You can enable/disable it in `Settings` page.
