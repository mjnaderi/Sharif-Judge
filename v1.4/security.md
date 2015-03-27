# Security

## Step 1. Use Sandbox

Make sure that you are using Sandbox for C/C++ and you have enabled Java Security Manager (Java Policy) for Java. You can read more about sandboxing [here](sandboxing.md).

## Step 2. Use Shield

Shield is not a real protection, but is more than nothing! Make sure that you have enabled Shield for C, C++ and Python. You can read more about Shield [here](shield.md).

## Step 3. Run as Non-Privileged User

It is very important to run submitted codes as a non-privileged user - a user who does not have access to the network, is not able to write any files, and is not able to create lots of processes.

I assume that PHP is running as user `www-data` on your server.
Create a new user `restricted_user` and set a password for it.
Run `sudo visudo` and add this line at the end of `sudoers` file:
  
    www-data ALL=(restricted_user) NOPASSWD: ALL

In `tester/runcode.sh` change

```bash
if $TIMEOUT_EXISTS; then
	timeout -s9 $((TIMELIMITINT*2)) $CMD <$IN >out 2>err
else
	$CMD <$IN >out 2>err        
fi
```
to
```bash
if $TIMEOUT_EXISTS; then
	sudo -u restricted_user timeout -s9 $((TIMELIMITINT*2)) $CMD <$IN >out 2>err
else
	sudo -u restricted_user $CMD <$IN >out 2>err        
fi
```

And uncomment this line:
```bash
sudo -u restricted_user pkill -9 -u restricted_user
```

### Disable Networking for restricted_user
`restricted_user` should not be able to access network. You can disable networking for a user in Linux using `iptables`.
Read more about this [here](http://www.cyberciti.biz/tips/block-outgoing-network-access-for-a-single-user-from-my-server-using-iptables.html) and [here](http://askubuntu.com/questions/102005/disable-networking-for-specific-users).
After disabling networking, test it by running `ping` as `restricted_user`.

### Deny Write Permissions
Just make sure that no file or directory is writable by `restricted_user`. Check your file and directory permissions.

### Limit Number of Processes
Limit number of processes of `restricted_user`.
Open `/etc/security/limits.conf` and add these lines:

    restricted_user     soft    nproc   3
    restricted_user     hard    nproc   5

I use 3, 5. You can use different soft and hard limits.

## Step 4. Use Two Servers

Use a server for web interface and handling web requests and use another server for running submitted codes. This decreases the risk of running submitted codes. You need to change Sharif Judge's source code to achieve this. Maybe I add this feature in future.


