# @file shield_py2.py

import sys
sys.modules['os']=None

BLACKLIST = [
  #'__import__', # deny importing modules
  'eval', # eval is evil
  'open',
  'file',
  'exec',
  'execfile',
  'compile',
  'reload',
  'input' # input in python 2 uses eval
  ]
for func in BLACKLIST:
  if func in __builtins__.__dict__:
    del __builtins__.__dict__[func]
